<?php

//$tools = [];

$tools['get_current_date']=[
'description'=>            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_current_date',
                    'description' => 'Возвращает текущую дату',
                    'parameters' => [ 'type' => 'object', 'properties' => [], 'required' => [] ]
                ]
            ],
'command'=>'dateTool'
];
function dateTool(): string {
        return "Текущая дата: " . date('d.m.Y');
}


$tools['get_current_time']=[
'description'=>
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_current_time',
                    'description' => 'Возвращает текущее время',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                        'required' => []
                    ]
                ]
            ],

'command'=>'timeTool'
];
function timeTool(): string {
        return "Текущее время: " . date('H:i:s');
    }


$tools['search_facts']=[
'description'=>
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_facts',
                    'description' => 'поиск информации в записной книжке',
                ]
            ],

'command'=>'search_facts'
];
$tools['search_facts']['description']['function']['parameters']= [
                        'type' => 'object',
                        'properties' =>  [
					'query' => ['type' => 'string', 'description' => 'Text'], 
					'limit' => ['type' => 'integer', 'default' => 10],
					'offset' => ['type' => 'integer', 'default' => 0]
			],
                        'required' => []
                    ];


$tools['search_facts']['command']='search_facts';

function search_facts($params)
{
  $query=db_escape($params['query']);

  $limit=10;
  if (isset($params['limit']))$limit=(int)$params['limit'];
  if ($limit >100)$limit=100;

  $offset=0;
  if (isset($params['offset']))$offset=(int)$params['offset'];

  $fact_embedding = '('.implode(",",getEmbedding($params['query'])).')';
/*   select * from facts where  match('$query')   union*/
  $sql= " 

   select id, fact_text, fact_tags  from facts where knn(fact_embedding, $fact_embedding ,{ ef=2000, oversampling=3.0, rescore=1 } )
   limit $limit offset $offset 
";
//   echo  "------------------\n $sql\n--------------------\n";

  $result=db_select($sql);
  return json_encode($result);
}




$tools['add_fact']=[
'description'=>
            [
                'type' => 'function',
                'function' => [
                    'name' => 'add_fact',
                    'description' => 'добавление новой записи в записную книжку',
                ]
            ],

'command'=>'add_fact'
];
$tools['add_fact']['description']['function']['parameters']= [
                        'type' => 'object',
			'properties' => ['fact' => ['type' => 'string', 'description' => 'Text'],
			'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
			'source' => ['type' => 'string']],
                        'required' => ['fact']
                    ];







function add_fact($params)
{
  $now=time();
  $fact=db_escape($params['fact']);
  $tags='';

  if (isset($params['tags']) )
  {
    $tmp_tags=$params['tags'];
    if (is_array($tmp_tags))
    {
      foreach ($tmp_tags as $key => $val) { $tmp_tags[$key] = db_escape($val); }
      $tags=implode(",", $tmp_tags);
    }
    else
    $tags=db_escape($tmp_tags);
  }

  $source='';
  if (isset($params['source']))  { $source=db_escape($params['source']); }
//echo "get embedding";
  $fact_embedding = '('.implode(",",getEmbedding($params['fact'])).')';
  //echo " complete\n";

   $sql="insert into facts(fact_text,fact_tags,source,created_at, fact_embedding) values('$fact', '$tags', '$source', $now, $fact_embedding )";
  // echo  "------------------\n $sql\n--------------------\n";
   db_exec($sql);
  return json_encode(['status' => 'saved']);
}


