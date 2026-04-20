data "aws_route53_zone" "main" {
  name = "tommykeyapp.com"
}

data "aws_acm_certificate" "wildcard" {
  provider = aws.us_east_1
  domain   = "*.tommykeyapp.com"
  statuses = ["ISSUED"]
}

resource "aws_route53_record" "burnnote" {
  zone_id = data.aws_route53_zone.main.zone_id
  name    = var.domain
  type    = "A"

  alias {
    name                   = aws_cloudfront_distribution.frontend.domain_name
    zone_id                = aws_cloudfront_distribution.frontend.hosted_zone_id
    evaluate_target_health = false
  }
}
