CREATE TABLE facts (
id bigint,
fact_text text,
fact_tags text,
source text,
created_at timestamp,
fact_embedding float_vector knn_type='hnsw' knn_dims='384' hnsw_similarity='COSINE'
)