# AWS Answers

## 1. Media delivery
How would you serve media files from a private S3 bucket to screens through CloudFront, without making the bucket public?

Keep the S3 bucket private and configure CloudFront with it as the origin.
Use Origin Access Control (OAC), then allow only that distribution in the S3 bucket policy.
Give screens short-lived CloudFront signed URLs or signed cookies when screen-level authorization is required.

## 2. Hosting
Which AWS services would you use to run the PHP page, Node.js API and database? Which parts would be publicly reachable, and why?

Run the PHP page on Elastic Beanstalk or ECS and the Node.js API on ECS, both behind an internet-facing HTTPS Application Load Balancer.
Expose the API to screens and restrict the admin path to trusted admins; run MariaDB on Amazon RDS in private subnets.
Allow database connections only from application security groups so clients cannot connect to the database directly.

## 3. IAM
Write an IAM policy that lets the Node.js API read objects from one bucket named `screenlite-media`, and nothing else.

Attach this least-privilege policy to the API's IAM role:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::screenlite-media/*"
    }
  ]
}
```

## 4. Production issue
The API starts returning 500 errors at 2 am. What monitoring would alert you, and what would you check first?

Use CloudWatch application logs and metrics, with alarms on API 5xx/error rates, CPU, memory, and RDS health; send alarm notifications through SNS.
First inspect the error logs and deployment/configuration changes, then check database availability, connections, and resource limits.
