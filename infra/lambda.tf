# Placeholder zip for initial Lambda creation (real code is deployed by CI/CD)
data "archive_file" "placeholder" {
  type        = "zip"
  output_path = "${path.module}/placeholder.zip"

  source {
    content  = "<?php http_response_code(503); echo 'placeholder';"
    filename = "public/index.php"
  }
}

# Stable APP_KEY generated and kept in tfstate (encrypted S3 backend)
resource "random_password" "app_key" {
  length  = 32
  special = false
}

resource "aws_lambda_function" "api" {
  function_name = "${var.project}-api"
  role          = aws_iam_role.lambda.arn
  handler       = "public/index.php"
  runtime       = "provided.al2023"
  architectures = ["arm64"]
  timeout       = 28
  memory_size   = 512

  layers = [var.bref_layer_arn]

  filename         = data.archive_file.placeholder.output_path
  source_code_hash = data.archive_file.placeholder.output_base64sha256

  lifecycle {
    ignore_changes = [filename, source_code_hash]
  }

  environment {
    variables = {
      BREF_RUNTIME                  = "fpm"
      APP_NAME                      = var.project
      APP_ENV                       = "production"
      APP_DEBUG                     = "false"
      APP_URL                       = "https://${var.domain}"
      APP_KEY                       = "base64:${base64encode(random_password.app_key.result)}"
      LOG_CHANNEL                   = "stderr"
      LOG_LEVEL                     = "warning"
      DB_CONNECTION                 = "null"
      SESSION_DRIVER                = "array"
      CACHE_STORE                   = "array"
      QUEUE_CONNECTION              = "sync"
      BROADCAST_CONNECTION          = "log"
      FILESYSTEM_DISK               = "local"
      DYNAMODB_TABLE                = aws_dynamodb_table.notes.name
      BURNNOTE_MAX_CIPHERTEXT_BYTES = "16384"
      BURNNOTE_MAX_EXPIRES_SEC      = "604800"
    }
  }

  tags = {
    Project = var.project
  }
}

resource "aws_cloudwatch_log_group" "lambda" {
  name              = "/aws/lambda/${aws_lambda_function.api.function_name}"
  retention_in_days = var.log_retention_days

  tags = {
    Project = var.project
  }
}

# IAM Role for Lambda
resource "aws_iam_role" "lambda" {
  name = "${var.project}-lambda"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "lambda.amazonaws.com"
        }
      }
    ]
  })

  tags = {
    Project = var.project
  }
}

resource "aws_iam_role_policy_attachment" "lambda_basic" {
  role       = aws_iam_role.lambda.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole"
}

resource "aws_iam_role_policy" "lambda_dynamodb" {
  name = "${var.project}-lambda-dynamodb"
  role = aws_iam_role.lambda.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "dynamodb:GetItem",
          "dynamodb:PutItem",
          "dynamodb:DeleteItem",
          "dynamodb:UpdateItem",
        ]
        Resource = aws_dynamodb_table.notes.arn
      }
    ]
  })
}

resource "aws_lambda_permission" "apigw" {
  statement_id  = "AllowAPIGatewayInvoke"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.api.function_name
  principal     = "apigateway.amazonaws.com"
  source_arn    = "${aws_apigatewayv2_api.api.execution_arn}/*/*"
}
