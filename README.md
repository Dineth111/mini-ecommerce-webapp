# LuxeCommerce - Mini E-Commerce Web Application (PHP + MySQL)

Welcome to **LuxeCommerce**, a complete, secure, and visually stunning Mini E-Commerce web application built using raw **PHP (Core PHP)**, a **MySQL database**, and **Bootstrap 5** with custom styling elements like glassmorphic containers, smooth CSS transitions, and premium gradients.

---

## 🌟 Key Features

### 👤 Customer Features
- **Security-First Accounts**: User registration and login system with passwords secured using Bcrypt hashing (`password_hash`).
- **Product Discovery**: Browse products dynamically with a unified search input and category filtering tabs.
- **Persistent Cart**: Database-backed cart that preserves user shopping carts across logins.
- **Stock Validation**: Live inventory checks that prevent adding or checking out quantities exceeding current stock levels.
- **Atomic Checkout**: Transaction-based checkout that deducts inventory levels and logs items at their purchase-time price.
- **Order History**: Personal dashboard to review and track past orders.

### 🛡️ Administrative Control Panel
- **Secure Gate**: Password-protected administrative login page.
- **Category Management**: Add, edit, or delete categories.
- **Product Management**: Full CRUD panel allowing admins to define descriptions, pricing, inventory, category links, and upload product photos.
- **Order Fulfilment**: Oversee all customer orders, review detailed purchase lists, and update order fulfillment states (*Pending*, *Shipped*, or *Delivered*).
- **Customer Audit**: Inspect registered customer lists and check their total order counts.

---

## 📂 Project Structure

The project has been organized following clean development patterns:

```text
/mini-ecommerce-web-app
│
├── config/
│   └── db.php                  # Global PDO connection & session startup
│
├── assets/
│   ├── css/
│   │   └── style.css           # Custom CSS (Glassmorphism, gradients, hover effects)
│   ├── js/
│   │   └── main.js             # Client utilities (Form match verification, alerts)
│   └── images/                 # Destination folder for uploaded product photos
│
├── includes/
│   ├── header.php              # Customer layout header (Dynamic cart count badge)
│   ├── footer.php              # Customer layout footer
│   ├── admin_header.php        # Admin panel header with sidebar & auth gate
│   └── admin_footer.php        # Admin panel footer layout
│
├── user/
│   ├── login.php               # Customer login
│   ├── register.php            # Customer registration
│   ├── orders.php              # Customer order history dashboard
│   └── logout.php              # customer session termination
│
├── admin/
│   ├── index.php               # Admin orders dashboard & metrics
│   ├── login.php               # Administrative login
│   ├── products.php            # Products CRUD management with uploads
│   ├── categories.php          # Category CRUD management
│   ├── users.php               # Customer user list and metrics
│   └── logout.php              # Admin session termination
│
├── database.sql                # Full SQL tables and default seed data
├── index.php                   # Homepage store front & search listing
├── product.php                 # Dynamic product detail page
├── cart.php                    # Shopping cart manager
├── checkout.php                # Shipping address checkout form
└── README.md                   # Setup guide and technical documentation
```

---

## 🛠️ Step-by-Step Setup Guide

This guide assumes you are using **XAMPP** on Windows.

### Step 1: Clone or Copy files
Copy all the files of this project into your XAMPP `htdocs` directory under a folder named:
`C:\xampp\htdocs\mini ecomarce web app`

### Step 2: Set Up the Database
1. Start the **Apache** and **MySQL** modules from your **XAMPP Control Panel**.
2. Open your browser and navigate to **phpMyAdmin**: `http://localhost/phpmyadmin/`
3. Click on the **SQL** tab.
4. Open the [database.sql](file:///c:/xampp/htdocs/mini%20ecomarce%20web%20app/database.sql) file, copy its contents, paste them into the SQL query box, and click **Go**.
   - This creates the database `luxe_commerce` and all necessary tables.
   - It also populates the database with categories, seed products, a customer account, and an admin account.

### Step 3: Access the Web Application
- **Customer Storefront**: `http://localhost/mini ecomarce web app/index.php`
- **Admin Panel**: `http://localhost/mini ecomarce web app/admin/login.php`

### Step 4: Sign In with Seed Credentials

| Account Role | Username / Email | Password |
| :--- | :--- | :--- |
| **Customer User** | `john_doe` or `john@example.com` | `userpassword` |
| **Administrator** | `admin` or `admin@luxecommerce.com` | `adminpassword` |

*(You can also use the registration form to sign up new custom customer accounts!)*

---

## 🔒 Security Implementations

1. **SQL Injection Prevention**: We use **PDO Prepared Statements** for all database queries. Direct raw variable concatenation in SQL has been banned. Parameters are bound securely during execution.
2. **Bcrypt Password Protection**: Customer and admin passwords are never stored in plain text. They are hashed using the secure, industry-standard Bcrypt algorithm (`password_hash`) and verified securely via `password_verify`.
3. **Session-based Access Control**: 
   - Public customer functions like Cart, Checkout, and Order History check if `$_SESSION['user_id']` is present.
   - Admin pages are protected globally by a gate at the top of the header template, rejecting any user request that lacks active `$_SESSION['admin_id']` cookies and routing them to login.
4. **Data Isolation**: Customer sessions and Admin sessions use different keys (`user_id` and `admin_id`), preventing customers from gaining administrative access.
5. **Database Integrity via Transactions**: Checkout operations execute within an SQL Transaction block (`beginTransaction`). If checking stock levels, writing items, or deducting stock fails at any step, the database state is rolled back (`rollBack`), preventing orphan orders or half-completed sales records.

---

## 🎨 Design System (`style.css`)
We avoided standard Bootstrap looks by developing a premium visual style:
- **Typography**: Imported and set Google Font **Inter** for clean, readable text.
- **Harmonious Palette**: Styled with deep slate dark headers (`#0f172a`), beautiful active indigo-purple buttons (`linear-gradient(135deg, #6366f1, #4f46e5)`), and teal borders.
- **Glassmorphism**: Login cards and summaries use slightly translucent container backdrops with `backdrop-filter: blur(12px)` for a high-end, premium feel.
- **Smooth Animations**: Interactive elements scale and transition on hover (e.g. product cards lift up and add soft shadows when hovered).
