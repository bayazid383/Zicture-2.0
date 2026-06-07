<?php
// setup-database.php - Run once to create necessary database tables

require 'db.php';

try {
    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        rating INT DEFAULT 0,
        stock INT DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Products table created/verified<br>";

    $productColumns = [
        'sku' => "ALTER TABLE products ADD COLUMN sku VARCHAR(80) NULL AFTER id",
        'is_daily' => "ALTER TABLE products ADD COLUMN is_daily TINYINT(1) DEFAULT 0",
        'is_upcoming' => "ALTER TABLE products ADD COLUMN is_upcoming TINYINT(1) DEFAULT 0",
        'is_featured' => "ALTER TABLE products ADD COLUMN is_featured TINYINT(1) DEFAULT 1",
    ];
    foreach ($productColumns as $column => $alterSql) {
        $exists = $pdo->query("SHOW COLUMNS FROM products LIKE " . $pdo->quote($column))->fetch();
        if (!$exists) {
            $pdo->exec($alterSql);
            echo "Products column {$column} added<br>";
        }
    }

    // Create cart table
    $sql = "CREATE TABLE IF NOT EXISTS cart (
        id INT PRIMARY KEY AUTO_INCREMENT,
        session_id VARCHAR(255) NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )";
    $pdo->exec($sql);
    echo "Cart table created/verified<br>";

    // Create wishlist table
    $sql = "CREATE TABLE IF NOT EXISTS wishlist (
        id INT PRIMARY KEY AUTO_INCREMENT,
        session_id VARCHAR(255) NOT NULL,
        product_id INT NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )";
    $pdo->exec($sql);
    echo "Wishlist table created/verified<br>";

    // Create orders table
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        session_id VARCHAR(255) NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Orders table created/verified<br>";

    $orderColumns = [
        'subtotal' => "ALTER TABLE orders ADD COLUMN subtotal DECIMAL(10, 2) DEFAULT 0 AFTER total_price",
        'discount' => "ALTER TABLE orders ADD COLUMN discount DECIMAL(10, 2) DEFAULT 0 AFTER subtotal",
        'tax' => "ALTER TABLE orders ADD COLUMN tax DECIMAL(10, 2) DEFAULT 0 AFTER discount",
        'shipping' => "ALTER TABLE orders ADD COLUMN shipping DECIMAL(10, 2) DEFAULT 0 AFTER tax",
        'coupon_code' => "ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) NULL AFTER shipping",
        'customer_name' => "ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) NULL AFTER coupon_code",
        'customer_email' => "ALTER TABLE orders ADD COLUMN customer_email VARCHAR(255) NULL AFTER customer_name",
        'delivery_city' => "ALTER TABLE orders ADD COLUMN delivery_city VARCHAR(100) NULL AFTER customer_email",
        'delivery_address' => "ALTER TABLE orders ADD COLUMN delivery_address TEXT NULL AFTER delivery_city",
    ];
    foreach ($orderColumns as $column => $alterSql) {
        $exists = $pdo->query("SHOW COLUMNS FROM orders LIKE " . $pdo->quote($column))->fetch();
        if (!$exists) {
            $pdo->exec($alterSql);
            echo "Orders column {$column} added<br>";
        }
    }

    // Create order_items table
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )";
    $pdo->exec($sql);
    echo "Order items table created/verified<br>";

    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Admin users table created/verified<br>";

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = ?");
    $stmt->execute(['admin']);
    if ((int)$stmt->fetch()['count'] === 0) {
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
        echo "Default admin created: admin / admin123<br>";
    }

    // Insert sample products if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        $sampleProducts = [
            ['Organic Bread', 'food', 45, 'Fresh organic bread', 'image (44).jpg', 5, 50],
            ['Fresh Vegetables', 'food', 120, 'Fresh seasonal vegetables', 'image (164).jpg', 4, 100],
            ['Dairy Products', 'food', 80, 'Premium dairy products', 'image (124).jpg', 5, 75],
            ['Fruits Basket', 'food', 150, 'Assorted fresh fruits', 'image (26).jpg', 5, 60],
            ['Snacks Pack', 'food', 60, 'Variety snacks package', 'pexels-fox-225157.jpg', 4, 80],
            ['Action Game', 'games', 1500, 'Latest action game', 'image (44).jpg', 5, 20],
            ['Adventure Game', 'games', 1800, 'Epic adventure game', 'image (164).jpg', 4, 15],
            ['Strategy Game', 'games', 2000, 'Strategic gameplay', 'image (124).jpg', 5, 18],
            ['Sports Game', 'games', 1200, 'Popular sports game', 'image (26).jpg', 4, 25],
            ['Cotton Shirt', 'clothes', 800, 'Quality cotton shirt', '16052288-christmas-shopping-girl-with-bags-in-shopping-mall.webp', 4, 40],
            ['Jeans Pants', 'clothes', 1500, 'Premium jeans', 'image (164).jpg', 5, 30],
            ['Summer Dress', 'clothes', 1200, 'Casual summer dress', 'image (44).jpg', 5, 25],
            ['Casual T-Shirt', 'clothes', 500, 'Comfortable t-shirt', 'image (124).jpg', 4, 50],
            ['First Aid Kit', 'medicine', 750, 'Complete first aid', 'image (44).jpg', 5, 40],
            ['Thermometer', 'medicine', 300, 'Digital thermometer', 'image (164).jpg', 4, 60],
            ['Blood Pressure Monitor', 'medicine', 1500, 'Digital BP monitor', 'image (124).jpg', 5, 20],
            ['Vitamin Supplement', 'medicine', 600, 'Daily vitamins', 'image (26).jpg', 4, 80],
            ['Office Suite', 'software', 4500, 'Professional office', 'image (44).jpg', 5, 15],
            ['Antivirus Pro', 'software', 1200, 'Security software', 'image (164).jpg', 5, 30],
            ['Photo Editor', 'software', 2000, 'Advanced editor', 'image (124).jpg', 4, 25],
            ['Toy Building Blocks', 'kids', 500, 'Educational blocks', 'image (44).jpg', 5, 50],
            ['Puzzle Game', 'kids', 400, 'Fun puzzle', 'image (164).jpg', 4, 60],
            ['Kids Book Set', 'kids', 800, 'Story books', 'image (124).jpg', 5, 45],
            ['Power Drill', 'machineries', 3500, 'Heavy duty drill', 'image (44).jpg', 5, 10],
            ['Circular Saw', 'machineries', 5000, 'Professional saw', 'image (164).jpg', 4, 8],
            ['USB-C Cable', 'electronics', 250, 'Fast charging cable', 'image (44).jpg', 5, 200],
            ['Wireless Mouse', 'electronics', 800, 'Ergonomic mouse', 'image (164).jpg', 4, 100],
            ['Mechanical Keyboard', 'electronics', 2500, 'Gaming keyboard', 'image (124).jpg', 5, 50],
            ['Programming Book Set', 'books', 950, 'Helpful technology and learning books', 'pexels-pixabay-33487.jpg', 5, 35],
            ['Novel Collection', 'books', 700, 'Popular story books and novels', 'pexels-luis-quintero-2148216.jpg', 4, 45],
            ['Skin Care Bundle', 'beauty', 1250, 'Daily personal care and beauty bundle', 'remmake.png', 5, 30],
            ['Home Decor Set', 'home', 1800, 'Decorative home and living accessories', 'set.jpg', 4, 20],
            ['Fitness Kit', 'sports', 1600, 'Basic sports and fitness equipment', 'pexels-thallen-merlin-1630436.jpg', 4, 22],
            ['Car Care Pack', 'automotive', 1100, 'Cleaning and care essentials for vehicles', 'pexels-mike-b-102977.jpg', 4, 28],
            ['Student Stationery Pack', 'stationery', 420, 'Notebook, pen, pencil, and study supplies', 'pexels-pixabay-159711.jpg', 5, 80],
        ];

        $stmt = $pdo->prepare("INSERT INTO products (name, category, price, description, image, rating, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($sampleProducts as $product) {
            $stmt->execute($product);
        }
        echo "Sample products inserted<br>";
    }

    $extraProducts = [
        ['Gaming Laptop', 'electronics', 78000, 'High performance laptop for study, work, and gaming', 'pexels-josh-sorenson-1714208.jpg', 5, 12],
        ['Android Smartphone', 'electronics', 24500, 'Fast mobile phone with long battery life', 'mobile-app-development-1.png', 5, 30],
        ['Tablet Pro', 'electronics', 32000, 'Portable tablet for reading, work, and media', 'pexels-pixabay-163036.jpg', 4, 18],
        ['Noise Cancelling Headphones', 'electronics', 5200, 'Wireless headphones with clear sound and soft ear pads', 'image (106).jpg', 5, 26],
        ['Smart Watch Active', 'electronics', 6500, 'Fitness, calls, notifications, and daily tracking on wrist', 'image (120).jpg', 4, 34],
        ['Office Chair', 'home', 8500, 'Comfortable chair for work and study setup', 'pexels-julie-aagaard-2433868.jpg', 4, 16],
        ['Kitchen Storage Set', 'home', 1450, 'Clean storage boxes for kitchen and home organization', 'pexels-nicole-michalou-5779170.jpg', 4, 40],
        ['Cotton Bed Sheet', 'home', 1750, 'Soft bed sheet set for daily home comfort', 'pexels-kristina-paukshtite-1146760.jpg', 5, 32],
        ['LED Desk Lamp', 'home', 980, 'Compact reading lamp for study and office desks', 'pexels-kaique-rocha-90368.jpg', 4, 25],
        ['Math Practice Book', 'books', 350, 'Useful study book for daily practice', 'pexels-pixabay-33487.jpg', 4, 60],
        ['English Grammar Guide', 'books', 460, 'Simple grammar book for school and daily learning', 'pexels-pixabay-163036.jpg', 5, 48],
        ['Business Notebook Planner', 'books', 620, 'Planner and productivity notes for work and study', 'pexels-luis-quintero-2148216.jpg', 4, 38],
        ['Kids Story Collection', 'books', 580, 'Colorful story books for young readers', 'pexels-polesie-toys-4487907.jpg', 5, 44],
        ['Perfume Gift Box', 'beauty', 2200, 'Elegant personal care gift item', 'image (83).jpg', 5, 24],
        ['Face Wash Combo', 'beauty', 720, 'Daily face wash combo for fresh skin care', 'remmake.png', 4, 52],
        ['Makeup Essentials Kit', 'beauty', 1850, 'Useful beauty kit for daily styling and travel', 'image (35).jpg', 5, 27],
        ['Hair Care Pack', 'beauty', 1150, 'Shampoo, oil, and care products for healthy hair', 'image (36).jpg', 4, 31],
        ['Football', 'sports', 900, 'Durable sports ball for everyday play', 'pexels-pixabay-163546.jpg', 4, 35],
        ['Yoga Mat', 'sports', 780, 'Comfortable exercise mat for home workouts', 'pexels-thallen-merlin-1630436.jpg', 4, 42],
        ['Dumbbell Pair', 'sports', 2600, 'Adjustable dumbbell pair for strength training', 'pexels-bruno-massao-2873486.jpg', 5, 15],
        ['Running Shoes', 'sports', 2800, 'Lightweight shoes for running and daily fitness', 'pexels-edgars-kisuro-1488463.jpg', 5, 23],
        ['Notebook Bundle', 'stationery', 300, 'Colorful notebooks for class and office', 'pexels-luis-quintero-2148216.jpg', 4, 90],
        ['Premium Pen Set', 'stationery', 260, 'Smooth writing pens for school, office, and exams', 'pexels-pixabay-33487.jpg', 4, 120],
        ['Desk Organizer', 'stationery', 540, 'Compact organizer for pens, notes, and small tools', 'pexels-julie-aagaard-2433868.jpg', 5, 45],
        ['Art Color Pack', 'stationery', 680, 'Color pencils and markers for school projects', 'pexels-polesie-toys-4487907.jpg', 4, 55],
        ['Engine Oil', 'automotive', 1350, 'Quality engine care product', 'pexels-mike-b-191360.jpg', 4, 20],
        ['Car Cleaning Kit', 'automotive', 950, 'Cleaning cloth, polish, and dashboard care products', 'pexels-mike-b-102977.jpg', 4, 28],
        ['Motorbike Helmet', 'automotive', 3200, 'Protective helmet with comfortable inner padding', 'pexels-partha-sekhar-borah-1988624.jpg', 5, 14],
        ['Emergency Tool Set', 'automotive', 2100, 'Useful road emergency tools for car and bike owners', 'pexels-mike-b-191360.jpg', 4, 18],
        ['Organic Rice Pack', 'food', 780, 'Quality rice pack for family meals', 'image (27).jpg', 5, 70],
        ['Coffee Beans', 'food', 650, 'Roasted coffee beans for rich daily coffee', 'image (38).jpg', 4, 45],
        ['Kids Learning Tablet', 'kids', 2400, 'Educational toy tablet with learning activities', 'pexels-polesie-toys-4487907.jpg', 5, 33],
        ['Remote Control Car', 'kids', 1500, 'Fun rechargeable toy car for kids', 'pexels-pixabay-163036.jpg', 4, 26],
        ['Accounting Software', 'software', 3200, 'Business accounting and invoicing software license', 'image (133).jpg', 5, 14],
        ['Language Learning App', 'software', 900, 'Digital app subscription for language practice', 'mobile-app-development-1.png', 4, 40],
        ['Cordless Grinder', 'machineries', 6200, 'Portable grinding tool for workshop tasks', 'image (159).jpg', 4, 9],
        ['Water Pump', 'machineries', 7400, 'Useful water pump for home and small farm use', 'image (164).jpg', 5, 8],
        ['Puzzle Adventure Game', 'games', 980, 'Fun puzzle game for families and friends', 'pexels-pixabay-163036.jpg', 4, 30],
        ['VR Racing Game', 'games', 2600, 'Immersive racing game experience', 'pexels-kaique-rocha-90368.jpg', 5, 16],
        ['Winter Jacket', 'clothes', 2600, 'Warm jacket with modern casual styling', 'image (44).jpg', 5, 21],
        ['Formal Shoes', 'clothes', 2200, 'Comfortable formal shoes for office and events', 'pexels-edgars-kisuro-1488463.jpg', 4, 24],
        ['Digital Glucose Meter', 'medicine', 1700, 'Compact glucose meter for home health checks', 'image (120).jpg', 5, 19],
        ['Hand Sanitizer Pack', 'medicine', 280, 'Daily hygiene sanitizer pack for family use', 'image (38).jpg', 4, 100],
    ];
    $existsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE name = ?");
    $insertStmt = $pdo->prepare("INSERT INTO products (name, category, price, description, image, rating, stock, is_daily, is_upcoming, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($extraProducts as $index => $product) {
        $existsStmt->execute([$product[0]]);
        if ((int)$existsStmt->fetch()['count'] === 0) {
            $values = $product;
            $values[] = $index % 3 === 0 ? 1 : 0;
            $values[] = $index % 4 === 0 ? 1 : 0;
            $values[] = 1;
            $insertStmt->execute($values);
        }
    }
    $imageFixes = [
        'Math Practice Book' => 'pexels-pixabay-33487.jpg',
        'Notebook Bundle' => 'pexels-luis-quintero-2148216.jpg',
        'Novel Collection' => 'pexels-luis-quintero-2148216.jpg',
        'Programming Book Set' => 'pexels-pixabay-33487.jpg',
    ];
    $fixStmt = $pdo->prepare('UPDATE products SET image = ? WHERE name = ?');
    foreach ($imageFixes as $name => $image) {
        $fixStmt->execute([$image, $name]);
    }
    echo "Extra category products verified<br>";

    echo "<br><strong>Database setup complete!</strong>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
