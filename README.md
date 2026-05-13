# 🎯 SmartHire - Intelligent Recruitment Management System

> A comprehensive AI-powered recruitment platform built with Symfony 7.4, enabling seamless hiring workflows, candidate management, and intelligent job matching.

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Tech Stack](#tech-stack)
- [Core Features](#core-features)
- [Architecture & Module Integration](#architecture--module-integration)
- [Repository Metadata](#repository-metadata)
- [Installation & Setup](#installation--setup)
- [Database Configuration](#database-configuration)
- [Running the Application](#running-the-application)
- [Project Structure](#project-structure)
- [API Integrations](#api-integrations)

---

## 📌 Project Overview

**SmartHire** is an intelligent recruitment management system designed to streamline the entire hiring process through AI-powered analysis, candidate evaluation, and HR workflow automation. The platform supports **three distinct user roles** (Admin, HR Manager, and Candidate) with tailored interfaces and functionalities.

### Key Objectives:
- **Automate Recruitment**: Reduce manual hiring workload through intelligent candidate screening
- **Enhance Candidate Experience**: Provide an intuitive interface for job browsing and application submission
- **Support HR Operations**: Comprehensive dashboard for interview scheduling, event management, and analytics
- **Administrative Control**: Full system oversight with user management, compliance monitoring, and reporting

---

## 🛠️ Tech Stack

### Backend Framework
- **Symfony 7.4** - Modern PHP web framework with robust MVC architecture
- **PHP 8.2+** - Latest PHP features for type safety and performance

### Database & ORM
- **PostgreSQL 16** - Reliable relational database (Docker-based)
- **Doctrine ORM 3.6** - Object-relational mapping with advanced query capabilities
- **Doctrine Migrations 3.7** - Database schema versioning and management

### Frontend & Templating
- **Twig 3.x** - Powerful templating engine (57.5% of codebase)
- **Bootstrap 5.3** - Responsive UI framework for consistent styling
- **Stimulus.js** - Lightweight JavaScript framework for interactivity
- **Turbo** - Fast navigation and partial page updates

### AI & APIs Integration
- **Groq API** - Advanced AI model inference for candidate analysis
- **Hugging Face** - Machine learning model access
- **Google API Client** - OAuth authentication and Google services
- **Algolia Search** - Full-text search and indexing for job listings
- **FacePlus / Face.io** - Facial recognition for candidate verification
- **Twilio SMS** - SMS notification service for candidates
- **Adzuna Job API** - Job market data aggregation

### Utilities & Services
- **QR Code Generator** - (Endroid) Resume/profile QR codes
- **PDF Parser** - (Smalot) Resume parsing and text extraction
- **Vich Uploader** - File upload management for resumes/documents
- **Calendar Bundle** - Event scheduling and calendar management
- **Google Mailer** - Email integration via Gmail

### Development Tools
- **PHPUnit 11.5** - Unit and functional testing framework
- **PHPStan 2.1** - Static code analysis for quality assurance
- **Maker Bundle** - Code generation for entities, controllers, forms
- **Docker Compose** - Containerized local development environment

---

## ✨ Core Features

### 🔐 **Role-Based Access Control**

#### **Admin** (Blue Interface - #4686c2)
- System statistics and dashboard analytics
- User account management and permissions
- Job category and offer administration
- Application review and approval workflow
- Complaint/reclamation handling
- Training content management
- Event management system

#### **HR Manager** (Pink Interface - #e91e63)
- Dashboard with recruitment metrics
- Job offer creation and editing
- Interview scheduling and tracking
- Event organization and calendar management
- Quiz/assessment creation and evaluation
- Job request processing
- Candidate screening and evaluation

#### **Candidate** (Green Interface - #87a042)
- Personal dashboard with application status
- Job market browsing and advanced search
- Application management and tracking
- Event discovery and registration
- Skills assessment quizzes
- Training program access
- Support ticket system for complaints
- Profile and account management

### 💼 **Recruitment Management**
- Comprehensive job posting system with categorization
- Advanced candidate search and filtering
- Intelligent application tracking
- Interview scheduling and management
- Skills-based matching using AI

### 🧠 **AI-Powered Features**
- CV parsing and candidate profile generation
- Intelligent candidate-to-job matching
- Resume analysis using Groq AI
- Facial recognition for identity verification
- Natural language processing for job descriptions

### 📧 **Communication & Notifications**
- Email notifications via Gmail integration
- SMS alerts through Twilio
- Notification center for all users
- Event-based alert system

### 📊 **Analytics & Reporting**
- Recruitment funnel analytics
- Candidate pipeline visualization
- Application statistics
- Admin system monitoring

---

## 🏗️ Architecture & Module Integration

### **Database Architecture**

The system uses a **relational database design** with the following entity hierarchy:
