BudgetKu - PHP + MySQL CRUD Project
====================================

Instructions:
1. Import SQL: Open phpMyAdmin -> Import -> choose sql/budgetku.sql and run. 
   This creates database 'budgetku' and sample admin (username: admin, password: 12345).

2. Put the folder 'budgetku_project' into your webserver root (e.g., C:/xampp/htdocs/).
   So the path will be e.g. C:/xampp/htdocs/budgetku_project/public/index.php

3. Edit config in config/database.php if your DB user/password differ.

4. Access: http://localhost/budgetku_project/public/index.php

Files included:
- config/database.php
- sql/budgetku.sql
- public/*.php (index, register, dashboard, transaksi, kategori, target, logout)

Security notes:
- This is a simple starter project. For production, add CSRF protection, input validation and stronger auth.

