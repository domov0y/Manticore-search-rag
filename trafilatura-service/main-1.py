from flask import Flask, request, jsonify
import trafilatura
import requests
from urllib.parse import urlparse
import logging
import chardet  # для автоопределения кодировки (установите: pip install chardet)

logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s - %(levelname)s - %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)

app = Flask(__name__)

REQUEST_TIMEOUT = 10
MAX_CONTENT_SIZE = 18 * 1024 * 1024  # 2 МБ
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
}

def is_safe_url(url):
    logger.debug(f"Проверка безопасности URL: {url}")
    try:
        parsed = urlparse(url)
        if parsed.scheme not in ('http', 'https'):
            logger.warning(f"Отклонён URL с неподдерживаемой схемой: {parsed.scheme}")
            return False
        hostname = parsed.hostname or ''
        blocked = ('localhost', '127.0.0.1', '::1', '0.0.0.0')
        if hostname in blocked:
            logger.warning(f"Отклонён локальный адрес: {hostname}")
            return False
        logger.debug(f"URL прошел проверку безопасности: {url}")
        return True
    except Exception as e:
        logger.error(f"Ошибка при разборе URL: {e}")
        return False

@app.route('/extract', methods=['GET'])
def extract_text():
    logger.info("Получен новый запрос на /extract")
    url = request.args.get('url')
    if not url:
        logger.warning("Запрос отклонён: отсутствует параметр 'url'")
        return jsonify({"error": "Параметр 'url' обязателен"}), 400

    logger.info(f"Запрошен URL: {url}")

    if not is_safe_url(url):
        logger.warning(f"Небезопасный URL отклонён: {url}")
        return jsonify({"error": "URL не поддерживается или запрещён"}), 400

    try:
        logger.debug(f"Начинаем загрузку страницы: {url} (таймаут {REQUEST_TIMEOUT}с, лимит {MAX_CONTENT_SIZE} байт)")
        
        response = requests.get(url, timeout=REQUEST_TIMEOUT, stream=True, headers=HEADERS)
        response.raise_for_status()
        
        # Чтение с ограничением размера
        content = b''
        for chunk in response.iter_content(chunk_size=8192):
            content += chunk
            if len(content) > MAX_CONTENT_SIZE:
                logger.warning(f"Превышен максимальный размер ответа ({MAX_CONTENT_SIZE}) для {url}")
                return jsonify({"error": "Содержимое страницы слишком большое"}), 413
        
        # Определяем кодировку автоматически (для русского и других языков)
        detected = chardet.detect(content)
        encoding = detected.get('encoding', 'utf-8')
        logger.debug(f"Определена кодировка: {encoding} (уверенность: {detected.get('confidence', 0)})")
        
        try:
            html_text = content.decode(encoding)
        except (UnicodeDecodeError, LookupError):
            # fallback
            html_text = content.decode('utf-8', errors='replace')
        
        logger.debug(f"Загрузка успешна, получено {len(html_text)} байт")
        
        logger.debug("Начинаем извлечение текста (удаление шума)")
        text = trafilatura.extract(html_text)
        
        if not text:
            logger.warning(f"Не удалось извлечь текст из страницы: {url}")
            return jsonify({"error": "Не удалось извлечь текст из этой страницы"}), 404
        
        logger.info(f"Текст успешно извлечён, длина {len(text)} символов")
        logger.debug(f"Первые 100 символов текста: {text[:100]}...")
        
        logger.info(f"Отправка ответа для {url}")
        return jsonify({
            "url": url,
            "content": text
        })
        
    except requests.exceptions.Timeout:
        logger.error(f"Таймаут при загрузке {url}")
        return jsonify({"error": "Превышено время ожидания ответа от сервера"}), 504
    except requests.exceptions.RequestException as e:
        logger.error(f"Ошибка HTTP при загрузке {url}: {e}")
        return jsonify({"error": f"Ошибка при загрузке страницы: {str(e)}"}), 400
    except Exception as e:
        logger.exception(f"Непредвиденная ошибка при обработке {url}: {e}")
        return jsonify({"error": "Внутренняя ошибка сервера"}), 500

if __name__ == '__main__':
    logger.info("Запуск сервера на порту 5000")
    app.run(debug=False, port=5000)