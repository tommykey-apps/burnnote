data "aws_caller_identity" "current" {}

resource "aws_ssm_parameter" "cloudfront_distribution_id" {
  name  = "/${var.project}/cloudfront-distribution-id"
  type  = "String"
  value = aws_cloudfront_distribution.frontend.id
}

resource "aws_ssm_parameter" "frontend_bucket" {
  name  = "/${var.project}/frontend-bucket"
  type  = "String"
  value = aws_s3_bucket.frontend.bucket
}

resource "aws_ssm_parameter" "lambda_function_name" {
  name  = "/${var.project}/lambda-function-name"
  type  = "String"
  value = aws_lambda_function.api.function_name
}
