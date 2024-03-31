<?php
// Function to load products from JSON file
function loadProducts($file_path) {
    $products_json = file_get_contents($file_path);
    if ($products_json === false) {
        die("Error: Unable to load products.");
    }

    $products = json_decode($products_json, true);
    if ($products === null) {
        die("Error: Invalid JSON format in products file.");
    }

    return $products;
}

// Function to save products to JSON file
function saveProducts($file_path, $products) {
    $result = file_put_contents($file_path, json_encode($products, JSON_PRETTY_PRINT));
    if ($result === false) {
        die("Error: Unable to save products.");
    }
}

$products_file = 'products.json';
$products = loadProducts($products_file);

// Add 'id' field to products array
foreach ($products as $key => $product) {
    $products[$key]['id'] = $key + 1; // Assigning IDs incrementally starting from 1
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add stock
    if (!empty($_POST["product_name"])) {
        $product_name = $_POST["product_name"];
        $product_count = intval($_POST["product_count"]);
        $product_price = $_POST["product_price"];
        $product_link = $_POST["product_link"];

        // Find the product by name
        $found_product = null;
        foreach ($products as &$product) {
            if ($product['name'] === $product_name) {
                $product['count'] += $product_count;
                $found_product = $product;
                break;
            }
        }

        // If product is not found, add it
        if (!$found_product) {
            $products[] = array(
                'id' => count($products) + 1,
                'name' => $product_name,
                'count' => $product_count,
                'price' => $product_price,
                'link' => $product_link
            );
        }

        // Save products to JSON file
        saveProducts($products_file, $products);

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
        saveProducts($products_file, $products);

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
<link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>
<link rel="icon" type="image/x-icon" href="/img/favicon.ico">
</head>
<body>

<input type="text" id="searchInput" placeholder="Hledat podle produktu nebo ID..">
<button onclick="search()">Hledat</button> &nbsp; <a href="http://sklad.brnout.cz/pridani.php"><button>Přidat Produkt</button></a>

<table id="productTable">
<tr>
<th>ID</th> <!-- Move ID column first -->
<th>Produkt</th>
<th>Počet kusů</th>
<th>Cena</th>
<th>Odkaz</th>
<th>Přidat</th>
<th>Odebrat</th>
</tr>

<?php foreach ($products as $product): ?>
<tr <?php if ($product['count'] < 1) echo 'class="out-of-stock"'; ?>>
<td><?php echo $product['id']; ?></td> <!-- Display ID column first -->
<td><?php echo $product['name']; ?></td>
<td id="<?php echo $product['name']; ?>-count"><?php echo $product['count']; ?></td>
<td>CZK <?php echo $product['price']; ?></td>
<td><a href="<?php echo $product['link']; ?>" target="_blank">Odkaz</a></td>
<td>
<form method="post" action="">
<input type="hidden" name="product_name" value="<?php echo $product['name']; ?>">
<input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
<input type="hidden" name="product_link" value="<?php echo $product['link']; ?>">
<input type="number" name="product_count" value="1" min="1">
<button type="submit">+</button>
</form>
</td>
<td>
<form method="post" action="">
<input type="hidden" name="remove_product_name" value="<?php echo $product['name']; ?>">
<button type="submit">-</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>

<script>
function search() {
    var query = document.getElementById("searchInput").value.trim().toLowerCase();
    var table = document.getElementById("productTable");
    var rows = table.getElementsByTagName("tr");

    for (var i = 1; i < rows.length; i++) {
        var productName = rows[i].getElementsByTagName("td")[1]; // Adjust index for Product column
        var productId = rows[i].getElementsByTagName("td")[0]; // Adjust index for ID column
        if (productName && productId) {
            var name = productName.textContent.trim().toLowerCase();
            var id = productId.textContent.trim().toLowerCase();
            if (name.includes(query) || id.includes(query)) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}

document.getElementById("searchInput").addEventListener("input", search);
</script>

</body>
</html>
