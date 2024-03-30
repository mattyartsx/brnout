<?php
// Load products from JSON file
$products_file = 'products.json';
$products = json_decode(file_get_contents($products_file), true);

// Add or update products
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["product_name"])) {
        $product_name = $_POST["product_name"];
        $product_count = $_POST["product_count"];
        $product_price = $_POST["product_price"];
        $product_link = $_POST["product_link"];

        // Check if product already exists
        $existing_key = array_search($product_name, array_column($products, 'name'));
        if ($existing_key !== false) {
            // Update existing product
            $products[$existing_key]['count'] += $product_count;
        } else {
            // Add new product
            $products[] = array(
                'name' => $product_name,
                'count' => $product_count,
                'price' => $product_price,
                'link' => $product_link
            );
        }

        // Save products to JSON file
        file_put_contents($products_file, json_encode($products, JSON_PRETTY_PRINT));

        // Redirect to prevent form resubmission on page refresh
        header("Location: {$_SERVER['REQUEST_URI']}");
        exit();
    }

    // Remove stock
    if (!empty($_POST["remove_product_name"])) {
        $product_name = $_POST["remove_product_name"];
        // Find the product and decrement the count
        foreach ($products as &$product) {
            if ($product['name'] === $product_name) {
                $product['count']--;
                break;
            }
        }
        // Save products to JSON file
        file_put_contents($products_file, json_encode($products, JSON_PRETTY_PRINT));

        // Redirect to prevent form resubmission on page refresh
        header("Location: {$_SERVER['REQUEST_URI']}");
        exit();
    }
}

// Sort products by count
usort($products, function($a, $b) {
    return $a['count'] - $b['count'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Brnout Sklad</title>
<link rel="stylesheet" href="styles.css">
<script>
function removeStock(productName) {
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'remove_product_name=' + encodeURIComponent(productName),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to remove stock');
        }
        // Update count in UI
        const countElement = document.getElementById(productName + '-count');
        const newCount = parseInt(countElement.textContent) - 1;
        countElement.textContent = newCount;
        if (newCount < 1) {
            countElement.closest('tr').classList.add('out-of-stock');
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
</head>
<body>

<table id="productTable">
<tr>
<th>Produkt</th>
<th>Počet kusů</th>
<th>Cena</th>
<th>Odkaz</th>
<th>Přidat</th>
<th>Odebrat</th>
</tr>

<?php foreach ($products as $product): ?>
<tr <?php if ($product['count'] < 1) echo 'class="out-of-stock"'; ?>>
<td><?php echo $product['name']; ?></td>
<td id="<?php echo $product['name']; ?>-count"><?php echo $product['count']; ?></td>
<td>CZK <?php echo $product['price']; ?></td>
<td><a href="<?php echo $product['link']; ?>" target="_blank">Link</a></td>
<td>
<form method="post" action="">
<input type="hidden" name="product_name" value="<?php echo $product['name']; ?>">
<input type="hidden" name="product_count" value="1">
<input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
<input type="hidden" name="product_link" value="<?php echo $product['link']; ?>">
<button type="submit">Přidat</button>
</form>
</td>
<td>
<button onclick="removeStock('<?php echo $product['name']; ?>')">Odebrat</button>
</td>
</tr>
<?php endforeach; ?>
</table>

<form method="post" action="">
<h2>Přidání produktu</h2>
<label for="pname">Produkt</label>
<input type="text" id="pname" name="product_name" required>
<label for="pcount">Počet kusů</label>
<input type="number" id="pcount" name="product_count" value="1" min="1" required>
<label for="pprice">Cena</label>
<input type="number" id="pprice" name="product_price" step="0.01" required>
<label for="plink">Odkaz</label>
<input type="url" id="plink" name="product_link" required>
<button type="submit">Přidat produkt</button>
</form>

</body>
</html>
