CREATE TABLE docs_meta (
id bigint,
title text,
url text,
tags text,
doc_id bigint
)

CREATE TABLE chunks (
id bigint,
content text indexed,
doc_id bigint,
chunk_id integer,
embedding_vector float_vector knn_type='hnsw' knn_dims='384' hnsw_similarity='COSINE' model_name='sentence-transformers/all-MiniLM-L6-v2'
)
