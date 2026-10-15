как собирать и ставить в виде докер контейнера. 

перейти в  текущую папку
cd trafilatura-service
выполнить команды
```
docker build -t text-extractor:latest .
docker run -d --name text-extractor -p 5000:5000 --restart unless-stopped text-extractor:latest

#опционально сохранить образ
docker save text-extractor:latest -o text-extractor.tar
#скопировать на  хост где оно будет работать любым удобным образом. например закинуть его через веб интерфейс или scp  или ssh  или передать  через конвейер команд  прочитать, перевести в  base64  | канал ввода вывода выполнения команд на удаленный хост  "расшифровать base64 записать в файл"
#восстановить образ
docker load -i text-extractor.tar
```

для установки в zima os /casa os  после создания  образа докера и копирования на хост   создать новое кастомное приложение  и вписать следующий docker-compose
```
name: text-extractor

services:
  text-extractor:
    image: text-extractor:latest
    container_name: text-extractor
    ports:
      - "5000:5000"
    restart: unless-stopped
```
