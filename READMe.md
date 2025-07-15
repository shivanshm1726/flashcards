<div align="center">

# 🧠 FlashCards App

### *Master Any Subject with Smart Flashcards*

<img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
<img src="https://img.shields.io/badge/TailwindCSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="TailwindCSS">
<img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg)](https://github.com/yourusername/flashcards-app/graphs/commit-activity)

</div>

---

## 🌟 Features

<div align="center">

| 🎯 **Smart Learning** | 👥 **Collaborative** | 📊 **Progress Tracking** |
|:---:|:---:|:---:|
| Spaced repetition algorithm | Share decks with friends | Detailed statistics |
| Adaptive difficulty | Community deck library | Study goal tracking |
| Personalized review schedule | Real-time collaboration | Performance analytics |

</div>

### ✨ Core Features

- 🔐 **User Authentication** - Secure login/registration system
- 📚 **Deck Management** - Create, edit, delete, and organize flashcard decks
- 🎴 **Smart Flashcards** - Interactive cards with flip animations
- 🧠 **Spaced Repetition** - Scientifically-proven learning algorithm
- 📈 **Progress Tracking** - Monitor your learning journey
- 🎯 **Study Goals** - Set and achieve daily/weekly targets
- 🏷️ **Categories** - Organize decks by subject
- 🌐 **Multi-language** - Support for English, Spanish, French
- 📧 **Email Notifications** - Study reminders and progress updates
- 🎨 **Modern UI** - Beautiful, responsive design with animations

---

## 🚀 Quick Start

### Prerequisites

Before you begin, ensure you have the following installed:

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP)
- [Composer](https://getcomposer.org/) (PHP dependency manager)
- [VS Code](https://code.visualstudio.com/) (recommended editor)

### 📦 Installation

1. **Clone the repository**
   \`\`\`bash
   git clone https://github.com/yourusername/flashcards-app.git
   cd flashcards-app
   \`\`\`

2. **Install dependencies**
   \`\`\`bash
   composer install
   \`\`\`

3. **Setup XAMPP**
   - Start Apache and MySQL services
   - Copy project to \`C:\\xampp\\htdocs\\flashcards-app\\\`

4. **Database Setup**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create database: \`flashcards-app\`
   - Import SQL files from \`/scripts/\` folder:
     \`\`\`sql
     -- Run these in order:
     01-create-database.sql
     02-seed-sample-data.sql
     \`\`\`

5. **Configure Database**
   Update \`includes/db.php\` with your database credentials:
   \`\`\`php
   $host = 'localhost';
   $dbname = 'flashcards-app';
   $username = 'root';
   $password = '';
   \`\`\`

6. **Launch Application**
   Open your browser and navigate to:
   \`\`\`
   http://localhost/flashcards-app/pages/landing.php
   \`\`\`

---

## 🎮 How to Use

### 🔑 Getting Started

1. **Create Account**
   - Visit the landing page
   - Click "Sign Up" and create your account
   - Or use demo credentials: \`demo@example.com\` / \`password\`

2. **Dashboard Overview**
   - View your study statistics
   - Track daily progress
   - Access quick actions

### 📚 Managing Decks

<details>
<summary><b>Creating Your First Deck</b></summary>

1. Click **"Create New Deck"** from dashboard
2. Fill in deck details:
   - **Name**: Give your deck a descriptive title
   - **Description**: Brief explanation of the content
   - **Category**: Choose or create a category
3. Click **"Create Deck"**
4. Start adding flashcards!

</details>

<details>
<summary><b>Adding Flashcards</b></summary>

1. Navigate to your deck
2. Click **"Add Flashcard"**
3. Enter:
   - **Question**: Front of the card
   - **Answer**: Back of the card
   - **Difficulty**: Easy, Medium, or Hard
4. Save and repeat!

</details>

### 🎯 Studying Effectively

<details>
<summary><b>Study Sessions</b></summary>

1. **Regular Study**: Click "Study" on any deck
2. **Custom Sessions**: 
   - Select multiple decks
   - Set card limits
   - Filter by difficulty
3. **Spaced Repetition**: Cards appear based on your performance

</details>

<details>
<summary><b>Study Tips</b></summary>

- 📅 Study daily for best results
- 🎯 Set realistic daily goals
- 🔄 Review difficult cards more frequently
- 📊 Monitor your progress regularly
- 🏆 Celebrate achievements!

</details>

---

## 🛠️ Development Setup

### VS Code Extensions

Install these recommended extensions:

\`\`\`json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "xdebug.php-debug",
    "formulahendry.auto-rename-tag",
    "bradlc.vscode-tailwindcss"
  ]
}
\`\`\`

### Project Structure

\`\`\`
flashcards-app/
├── 📁 assets/              # Images and static files
├── 📁 config/              # Configuration files
├── 📁 includes/            # PHP includes and utilities
├── 📁 lang/                # Language files
├── 📁 pages/               # Application pages
│   ├── 🏠 landing.php      # Landing page
│   ├── 🔐 login.php        # User login
│   ├── 📊 index.php        # Dashboard
│   ├── 📚 my_decks.php     # Deck management
│   └── 🎴 study.php        # Study interface
├── 📁 scripts/             # Database scripts
├── 📁 src/                 # CSS and assets
├── 📁 vendor/              # Composer dependencies
├── 📄 composer.json        # PHP dependencies
└── 📖 README.md           # This file
\`\`\`

---

## 🎨 Screenshots

<div align="center">

### 🏠 Landing Page
*Beautiful, modern landing page with feature highlights*

### 📊 Dashboard
*Comprehensive overview of your learning progress*

### 🎴 Study Interface
*Interactive flashcards with smooth animations*

### 📚 Deck Management
*Easy-to-use deck creation and organization*

</div>

---

## 🔧 Configuration

### Email Notifications

Update \`config/email.php\`:

\`\`\`php
return [
    'host' => 'smtp.gmail.com',
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password',
    'port' => 587,
    'encryption' => 'tls'
];
\`\`\`

### Database Configuration

Modify \`includes/db.php\` for different environments:

\`\`\`php
// Development
$host = 'localhost';
$dbname = 'flashcards-app';

// Production
$host = 'your-production-host';
$dbname = 'your-production-db';
\`\`\`

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### 🐛 Bug Reports

1. Check existing issues first
2. Create detailed bug report
3. Include steps to reproduce
4. Add screenshots if applicable

### ✨ Feature Requests

1. Search existing feature requests
2. Describe the feature clearly
3. Explain the use case
4. Consider implementation complexity

### 🔧 Pull Requests

1. Fork the repository
2. Create feature branch: \`git checkout -b feature/amazing-feature\`
3. Commit changes: \`git commit -m 'Add amazing feature'\`
4. Push to branch: \`git push origin feature/amazing-feature\`
5. Open Pull Request

---

## 📈 Roadmap

### 🎯 Version 2.0

- [ ] 📱 Mobile app (React Native)
- [ ] 🤖 AI-powered card generation
- [ ] 🎮 Gamification system
- [ ] 📊 Advanced analytics
- [ ] 🌐 API for third-party integrations

### 🔮 Future Ideas

- [ ] 🎵 Audio flashcards
- [ ] 🖼️ Image-based cards
- [ ] 👥 Study groups
- [ ] 🏆 Leaderboards
- [ ] 📚 Textbook integration

---

## 🆘 Troubleshooting

<details>
<summary><b>Common Issues</b></summary>

**Database Connection Error**
- Ensure MySQL is running in XAMPP
- Check database credentials in \`includes/db.php\`
- Verify database exists

**Composer Dependencies**
\`\`\`bash
composer install --no-dev
composer dump-autoload
\`\`\`

**Permission Issues**
- Ensure proper file permissions
- Check XAMPP directory access

**Email Not Working**
- Verify SMTP settings
- Check firewall/antivirus
- Use app-specific passwords for Gmail

</details>

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- 💡 Inspired by Anki and Quizlet
- 🎨 UI components from TailwindCSS
- 📧 Email functionality via PHPMailer
- 🧠 Spaced repetition algorithm research

---

<div align="center">

### 🌟 Star this repository if you found it helpful!

**Made with ❤️ by [Your Name]**

[⬆ Back to Top](#-flashcards-app)

</div>
