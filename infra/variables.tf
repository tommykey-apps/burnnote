variable "region" {
  description = "AWS region"
  type        = string
  default     = "ap-northeast-1"
}

variable "project" {
  description = "Project name"
  type        = string
  default     = "burnnote"
}

variable "domain" {
  description = "Public domain"
  type        = string
  default     = "burnnote.tommykeyapp.com"
}

variable "bref_fpm_layer_arn" {
  description = "Bref arm-php-84-fpm Lambda layer ARN (ap-northeast-1)"
  type        = string
  default     = "arn:aws:lambda:ap-northeast-1:534081306603:layer:arm-php-84-fpm:28"
}

variable "log_retention_days" {
  description = "CloudWatch Logs retention (days)"
  type        = number
  default     = 7
}
