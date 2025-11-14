<?php
function get_next_doc_id()
{
  global  $connection; 
  $query="SELECT MAX(doc_id) as doc_id FROM docs_meta ";
  $res = mysqli_query($connection, $query);
  if (mysqli_num_rows($res) > 0) 
  {
    $row = mysqli_fetch_assoc($res);
    return $row['doc_id']+1;
  }
  return 0;
}

/*--------------------------------------------------------------------------------*/
function split_to_chunks($filename)
{
  $tmp = file($filename);

  $chunks = array();
  $chunk = "";
  for ($i = 0; $i < count($tmp); $i++) 
  {
    $chunk .= $tmp[$i];
    if (strlen($chunk) >= 2000) 
    {
        $chunks[] = $chunk;
        $chunk = "";
    }
  }

  if (strlen($chunk) > 0) {   $chunks[] = $chunk;}
  return $chunks;
}

/*--------------------------------------------------------------------------------*/

function insert_chunks($doc_id, $chunks)
{
  global  $connection; 
  for ($i=0;$i<count($chunks);$i++)
  {
    $chunk=mysqli_real_escape_string($connection, $chunks[ $i ]);
    $query="insert into chunks(doc_id,chunk_id,content) values($doc_id,$i, '$chunk')";
    $res = mysqli_query($connection, $query);
  }
}
/*--------------------------------------------------------------------------------*/

function insert_meta($doc_id, $title, $url)
{
  global  $connection; 
  $doc_title = mysqli_real_escape_string($connection, $title);
  $doc_url   = mysqli_real_escape_string($connection, $url);

  $query="insert into docs_meta(doc_id , title , url, tags ) values( $doc_id, '$doc_title', 'https://habr.com/','')";
  mysqli_query($connection, $query);
}
/*--------------------------------------------------------------------------------*/
$options = getopt("", ["title:", "url:", "file:"]);

$connection = mysqli_connect("localhost:9306", "manticore", "", "") or die("imposible connect to server");

$doc_id= get_next_doc_id();
$doc_title= $options['title'];
$doc_url= $options['url'];
insert_meta($doc_id, $doc_title, $doc_url);
insert_chunks($doc_id,  split_to_chunks($options['file']);

mysqli_close($connection);
?>