# AWS Answers

## 1. Media Delivery
**How would you serve media files from a private S3 bucket to screens through CloudFront, without making the bucket public?**

Keep the S3 bucket private and configure CloudFront with it as the origin.
Use Origin Access Control (OAC) and allow only the CloudFront distribution in the S3 bucket policy.
Provide screens with short-lived CloudFront signed URLs or signed cookies when screen-level authorization is required.

## 2. Hosting
**Which AWS services would you use to run the PHP page, Node.js API and database? Which parts would be publicly reachable, and why?**

Run the PHP application on Elastic Beanstalk or ECS and the Node.js API on ECS behind an internet-facing HTTPS Application Load Balancer.
Expose the API so screens can send heartbeat requests and retrieve playlists.
Restrict the PHP admin path to trusted administrators, for example through a VPN or IP allow-list at the load balancer.
Run MariaDB on Amazon RDS in private subnets, allowing connections only from application security groups.

## 3. IAM
**Write an IAM policy that lets the Node.js API read objects from one bucket named `screenlite-media`, and nothing else.**

Attach this least-privilege policy to the Node.js API's IAM role:

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

## 4. Production Issue
**The API starts returning 500 errors at 2 am. What monitoring would alert you, and what would you check first?**

Use CloudWatch logs and metrics, with alarms on API 5xx rates, CPU, memory, and RDS health; use CloudWatch Agent or Container Insights for memory metrics where needed.
Send alarm notifications through SNS. First inspect error logs and recent deployment/configuration changes, then check database availability, connections, and resource limits.
