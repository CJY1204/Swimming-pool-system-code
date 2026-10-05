# SplashPoint — Cloud-Based Swimming Pool Booking System

A scalable, secure, and cost-optimized booking platform for TAR UMT's Campus Aquatic Center, deployed as a proof-of-concept on AWS — built as a team project for the *Cloud Computing for Business* course.

> **Note:** This project was deployed using an **AWS Academy Learner Lab** sandbox environment, which automatically reclaims all resources once the lab session ends. As a result, the live deployment URL is no longer active. Architecture diagrams, configuration screenshots, and load-test results are documented below and in the full project report.

## Overview

**Problem:** Manual booking methods for campus pools caused long queues, scheduling conflicts, and system overload during peak periods.

**Solution:** SplashPoint — a web-based booking system covering 3 campus pools (1 Olympic-size lane pool, 2 leisure/training pools), supporting public browsing, member accounts, real-time availability checking, and admin management — deployed on a production-grade AWS architecture designed around five pillars: **Security, High Performance, Scalability, Load Balancing, and Cost Optimization**.

## Architecture

- **Network:** Custom VPC across 2 Availability Zones, each with a public and private subnet (2 public + 2 private total)
- **Compute:** 2× EC2 instances (private subnet) behind an Application Load Balancer (ALB), managed by an Auto Scaling Group
- **Database:** Amazon RDS (MySQL), isolated in a private subnet
- **Storage:** Amazon S3 for application images/assets
- **Security:** NAT Gateway + Elastic IP for secure outbound access, layered Security Groups, AWS WAF, SSM Parameter Store for encrypted secrets
- **Monitoring & Messaging:** CloudWatch (alarms + dashboard), AWS CloudTrail, SNS + Lambda for event-driven email notifications

### Zero-Trust Network Design
EC2 (application layer) and RDS (database layer) are fully isolated in private subnets with no direct exposure to the internet. The only external entry point is the ALB. Security Groups enforce a strict one-way trust chain: **ALB → EC2 → RDS**, with each layer only trusting traffic from the layer directly before it.

## Key Features

### 🔒 Secure
- Zero-trust network isolation (VPC, private subnets, layered Security Groups)
- Encrypted secrets management via SSM Parameter Store (SecureString) with IAM Role-based retrieval at boot
- Member passwords hashed with bcrypt (no plaintext storage)
- AWS WAF rules blocking SQL injection and XSS — verified via simulated attack testing (`?id=1' OR '1'='1`), confirmed 403 responses

### ⚡ High Performing & Scalable
- Auto Scaling Group with Target Tracking policy (70% CPU threshold)
- Load tested with Apache Bench — 50 concurrent users, 50,000 requests, **zero failed requests**
- Verified live scale-out (1 → 2 instances) under load and automatic scale-in once load subsided

### ⚖️ Load Balanced
- Application Load Balancer distributing traffic evenly across 2 Availability Zones
- Verified even traffic distribution via access log comparison (<5% difference between instances) under a 5,000-request / 200-concurrency test

### 💰 Cost Optimized
- Right-sized resources (t3.micro EC2, db.t3.micro RDS) for a small-workload estimate of **~USD 111.59/month**
- Free-tier/negligible-cost services used where possible (SSM Parameter Store, CloudTrail, VPC/Security Groups, Auto Scaling)

### ✉️ Additional Services
- Lambda + SNS for automated booking confirmation email notifications
- S3 for serving application images via a configured base URL

## Tech Stack

- **Cloud Platform:** AWS (VPC, EC2, RDS, ALB, Auto Scaling Group, S3, Lambda, SNS, WAF, CloudWatch, CloudTrail, SSM Parameter Store, IAM)
- **Application:** PHP, MySQL
- **Testing Tools:** Apache Bench (`ab`)

## My Role

**Project Leader** — coordinated the team and personally designed/implemented:
- **Security architecture:** VPC network isolation, Security Group rules, zero-trust design, SSM Parameter Store secrets, WAF rule configuration and attack testing
- **Load Balancing & Scalability:** ALB setup, Auto Scaling Group with Target Tracking policy, CloudWatch alarms/dashboard, and load testing (Apache Bench) to verify scale-out/scale-in behavior
- **Additional services integration:** Lambda + SNS email notification pipeline, S3 image storage configuration

## Challenges & Solutions

| Challenge | Solution |
|---|---|
| AWS Academy Learner Lab IAM restrictions blocked certain services (CloudFront, SES) | Pivoted to alternatives (Gmail SMTP) after testing cheaply first |
| PHP sessions broke across multiple EC2 instances behind the ALB | Implemented a database-backed session handler |
| New Auto Scaling instances launched with outdated configuration | Moved setup/bootstrap logic into the Launch Template's User Data script |

## Future Improvements

- Migrate to HTTPS with a custom domain + ACM certificate
- Automate deployment via S3 + Instance Refresh (CI/CD-style)
- Move email confirmation to Amazon SES for production use
- Explore VPC Endpoints to remove NAT Gateway dependency

---
*Developed as part of AMIT3253 Cloud Computing for Business, TAR University of Management and Technology.*
