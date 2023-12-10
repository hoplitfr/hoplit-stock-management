<?php
/**
 * Plugin Name: Hoplit Stock Management
 * Description: A simple stock display solution for your WordPress website.
 * Version: 1.0
 * Author: Hoplit.fr
 * Author URI: https://www.hoplit.fr/
 * License: GPL v3
 * Text Domain: hoplit-stock-management
 */

// Activation hook
register_activation_hook( __FILE__, 'hoplit_stock_management_activation' );

// Activation callback
function hoplit_stock_management_activation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_management';

    // Check if table exists
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
        $charset_collate = $wpdb->get_charset_collate();

        // Table creation
        $sql = "CREATE TABLE $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            date DATETIME NOT NULL,
            name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

function hoplit_stock_management_admin_menu() {
    add_menu_page(
        'Hoplit Stock Management',
        'Stock Management',
        'manage_options',
        'hoplit_stock_management',
        'hoplit_stock_management_admin_page',
        'dashicons-store',
    );

    add_submenu_page(
        'hoplit_stock_management',
        'Add Items',
        'Add Items',
        'manage_options',
        'hoplit_stock_management_add_items',
        'hoplit_stock_management_add_items_page'
    );
	
	add_submenu_page(
        'hoplit_stock_management',
        'Reset Stock Data',
        'Reset',
        'manage_options',
        'hoplit_stock_management_reset',
        'hoplit_stock_management_reset_page'
    );

    add_submenu_page(
        NULL,
        'Edit Item',
        'Edit Item',
        'manage_options',
        'hoplit_stock_management_edit_item',
        'hoplit_stock_management_edit_item_page'
    );

    add_submenu_page(
        NULL,
        'Delete Item',
        'Delete Item',
        'manage_options',
        'hoplit_stock_management_delete_item',
        'hoplit_stock_management_delete_item_page'
    );
    
}
add_action('admin_menu', 'hoplit_stock_management_admin_menu');


function hoplit_stock_management_admin_page() {
    // Get data from db
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_management';
    $stocks = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    echo '<div class="wrap">';
    echo '<h1>Hoplit Stock Management</h1>';
    
	echo '<div class="notice notice-info">';
    echo '<p>To display this stock table on your website, use the shortcode <code>[hoplit_stock_management_table]</code>.</p>';
    echo '</div>';	
    // HTML table display stocks
    echo '<table class="wp-list-table widefat fixed">';
    echo '<thead><tr>';
    echo '<th>Last update</th>';
    echo '<th>Name</th>';
    echo '<th>Quantity</th>';
    echo '<th>Price</th>';
    echo '<th>Actions</th>';
    echo '</tr></thead>';
    
    echo '<tbody>';
    foreach ($stocks as $stock) {
        echo '<tr>';
        echo '<td>' . $stock['date'] . '</td>';
        echo '<td>' . $stock['name'] . '</td>';
        echo '<td>' . $stock['quantity'] . '</td>';
        echo '<td>' . $stock['price'] . '</td>';
        echo '<td>';
        echo '<a href="' . admin_url('admin.php?page=hoplit_stock_management_edit_item&id=' . $stock['id']) . '">Edit</a> | ';
        echo '<a href="' . admin_url('admin.php?page=hoplit_stock_management_delete_item&id=' . $stock['id']) . '">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    
    echo '</table>';
    echo '</div>';
}

function hoplit_stock_management_add_items_page() {
    echo '<div class="wrap">';
    echo '<h1>Add Items</h1>';
    
    // Add form
    echo '<form method="post">';
    echo '<label for="item_name">Item Name:</label><br>';
    echo '<input type="text" name="item_name" required><br>';
    
    echo '<label for="item_quantity">Quantity:</label><br>';
    echo '<input type="number" name="item_quantity" required><br>';
    
    echo '<label for="item_price">Price:</label><br>';
    echo '<input type="text" name="item_price" required><br>';
    
	echo '<br>';
	
    echo '<input type="submit" name="add_item" value="Add Item">';
    echo '</form>';
    
    echo '</div>';
}

function hoplit_stock_management_process_form() {
    if (isset($_POST['add_item'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';
        
        $item_name = sanitize_text_field($_POST['item_name']);
        $item_quantity = intval($_POST['item_quantity']);
        $item_price = floatval($_POST['item_price']);
        
        $wpdb->insert(
            $table_name,
            array(
                'date' => current_time('mysql'),
                'name' => $item_name,
                'quantity' => $item_quantity,
                'price' => $item_price
            )
        );
        
        echo '<div class="notice notice-success"><p>Item added successfully!</p></div>';
		echo '<a href="' . admin_url('admin.php?page=hoplit_stock_management') . '" class="button">Back to Main Page</a></div>';
    }
}
add_action('admin_notices', 'hoplit_stock_management_process_form');

// Edit item
function hoplit_stock_management_edit_item_page() {
    if (isset($_GET['id']) && intval($_GET['id']) > 0) {
        $item_id = intval($_GET['id']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';
        $item = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $item_id", ARRAY_A);

        echo '<div class="wrap">';
        echo '<h1>Edit Item</h1>';

        if ($item) {
            if (isset($_POST['update_item'])) {
                $new_item_name = sanitize_text_field($_POST['item_name']);
                $new_item_quantity = intval($_POST['item_quantity']);
                $new_item_price = floatval($_POST['item_price']);

                // Mettre à jour l'élément avec la nouvelle date
                $wpdb->update(
                    $table_name,
                    array(
                        'date' => current_time('mysql'),
                        'name' => $new_item_name,
                        'quantity' => $new_item_quantity,
                        'price' => $new_item_price
                    ),
                    array('id' => $item_id)
                );
                
                // Retrieve the new value of the edited item
                $item = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $item_id", ARRAY_A);

                echo '<div class="notice notice-success"><p>Item updated successfully!</p></div>';
				echo '<a href="' . admin_url('admin.php?page=hoplit_stock_management') . '" class="button">Back to Main Page</a></div>';
            }

            // Show existing data in form
            echo '<form method="post">';
            echo '<label for="item_name">Item Name:</label><br>';
            echo '<input type="text" name="item_name" value="' . esc_attr($item['name']) . '" required><br>';

            echo '<label for="item_quantity">Quantity:</label><br>';
            echo '<input type="number" name="item_quantity" value="' . esc_attr($item['quantity']) . '" required><br>';

            echo '<label for="item_price">Price:</label><br>';
            echo '<input type="text" name="item_price" value="' . esc_attr($item['price']) . '" required><br>';
			
			echo '<br>';
			
            echo '<input type="submit" name="update_item" value="Update Item">';
            echo '</form>';
        } else {
            echo '<div class="notice notice-error"><p>Item not found.</p></div>';
        }

        echo '</div>';
    } else {
        echo '<div class="notice notice-error"><p>Invalid item ID.</p></div>';
    }
}



// Delete item
function hoplit_stock_management_delete_item_page() {
    if (isset($_GET['id']) && intval($_GET['id']) > 0) {
        $item_id = intval($_GET['id']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';

        if (isset($_POST['delete_item'])) {
            $wpdb->delete($table_name, array('id' => $item_id));
            echo '<div class="notice notice-success"><p>Item deleted successfully!</p></div>';
			echo '<a href="' . admin_url('admin.php?page=hoplit_stock_management') . '" class="button">Back to Main Page</a></div>';
        }

        $item = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $item_id", ARRAY_A);

        echo '<div class="wrap">';
        echo '<h1>Delete Item</h1>';

        if ($item) {
            echo '<p>Are you sure you want to delete the following item?</p>';
            echo '<p>Name: ' . esc_attr($item['name']) . '</p>';
            echo '<p>Quantity: ' . esc_attr($item['quantity']) . '</p>';
            echo '<p>Price: ' . esc_attr($item['price']) . '</p>';
            echo '<form method="post">';
            echo '<input type="hidden" name="delete_item" value="1">';
            echo '<input type="submit" class="button button-primary" value="Delete">';
            echo '</form>';
        } else {
            echo '<div class="notice notice-error"><p>Item not found.</p></div>';
        }

        echo '</div>';
    } else {
        echo '<div class="notice notice-error"><p>Invalid item ID.</p></div>';
    }
}

function hoplit_stock_management_display_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stock_management';
    $stocks = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    $content = '<table class="hoplit-stock-management-table">';
    $content .= '<thead><tr>';
    $content .= '<th>Last update</th>';
    $content .= '<th>Name</th>';
    $content .= '<th>Quantity</th>';
    $content .= '<th>Price</th>';
    $content .= '</tr></thead>';

    $content .= '<tbody>';
    foreach ($stocks as $stock) {
        $content .= '<tr>';
        $content .= '<td>' . esc_attr($stock['date']) . '</td>';
        $content .= '<td>' . esc_attr($stock['name']) . '</td>';
        $content .= '<td>' . esc_attr($stock['quantity']) . '</td>';
        $content .= '<td>' . esc_attr($stock['price']) . '</td>';
        $content .= '</tr>';
    }
    $content .= '</tbody>';

    $content .= '</table>';

    return $content;
}

function hoplit_stock_management_shortcode() {
    ob_start();
    echo hoplit_stock_management_display_table();
    return ob_get_clean();
}
add_shortcode('hoplit_stock_management_table', 'hoplit_stock_management_shortcode');

function hoplit_stock_management_reset_page() {
    if (isset($_POST['reset_data'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'stock_management';
        $wpdb->query("TRUNCATE TABLE $table_name"); // Empty table

        echo '<div class="notice notice-success"><p>Stock data reset successfully!</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Reset Stock Data</h1>';
    echo '<p>Click the button below to reset stock data.</p>';
	echo '<div class="notice notice-error"><p><b>Warning :</b> Stock data is about to be lost.</p></div>';
    echo '<form method="post">';
    echo '<input type="hidden" name="reset_data" value="1">';
    echo '<input type="submit" class="button button-primary" value="Reset Stock Data">';
    echo '</form>';
    echo '</div>';
}
