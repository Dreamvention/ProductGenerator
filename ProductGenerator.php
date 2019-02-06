<?php

$generator = new ProductGenerator();

Class ProductGenerator {

    private $PGversion = "1.0.0";

    private $numTopCategories = 0;
    private $numSubCategories = 0;
    private $numProducts = 0;
    private $defaultCategories = array(
        'Clothing',
        'Dresses',
        'Tops Tees & Blouses',
        'Sweaters',
        'Fashion Hoodies & Sweatshirts',
        'Jeans',
        'Pants',
        'Skirts',
        'Shorts',
        'Leggings',
        'Active',
        'Swimsuits & Cover Ups',
        'Lingerie, Sleep & Lounge',
        'Jumpsuits, Rompers & Overalls',
        'Coats, Jackets & Vests',
        'Suiting & Blazers',
        'Socks & Hosiery',
        'Shoes',
        'Watches',
        'Handbags & Wallets',
        'Accessories');
    private $defaultProducts = array(
        'Navy or grey two piece suit',
        'Grey wool trousers',
        'Dark wash jeans',
        'Tan chinos ',
        'White dress shirt',
        'Solid color button up shirt',
        'Solid color t-shirt',
        'Solid color sweater',
        'Leather dress shoes',
        'Classic white sneakers',
        'Necktie','Leather belt',
        'Navy or grey two piece suit',
        'Grey wool trousers',
        'Dark wash jeans',
        'Tan chinos',
        'White dress shirt',
        'Solid color button up shirt',
        'Solid color t-shirt',
        'Solid color sweater'
    );
    private $productDescription = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc pulvinar massa mauris
        id posuere sem interdum a. Aenean nec lorem neque. Nunc sed nulla faucibus, dapibus mauris quis, porttitor ante. 
        Nunc tempus mattis tempor. Fusce non molestie tortor, et lobortis mauris. Sed viverra nulla ac libero tempor, 
        in commodo nisi laoreet. Suspendisse bibendum lectus eu orci ornare blandit. Fusce faucibus sollicitudin augue
        eget tempus. Duis vitae dapibus massa, vel elementum diam.<br><br>Integer faucibus risus felis, id lacinia 
        felis feugiat eget. Sed sit amet nisl vel est volutpat tristique vel at justo. Suspendisse varius neque in 
        sollicitudin bibendum. Proin elit odio, tincidunt id risus at, fermentum efficitur dui. Sed pellentesque 
        eros id pharetra vulputate. Maecenas et ultrices massa, eget malesuada lacus. Maecenas urna magna, tristique 
        ac felis vitae, sagittis faucibus dui.<br><br>Nullam non nisi nisi. Pellentesque bibendum commodo efficitur. 
        Quisque rutrum tristique dui vitae condimentum. Curabitur risus felis, semper ac lobortis sed, pharetra vel 
        erat. Praesent et orci sit amet purus faucibus convallis. Proin sed lacus eu massa congue volutpat. Maecenas 
        vulputate vulputate dolor eu euismod.';

    // Attribute && Attribute Group
    private $attributeState = false;
    private $attributeNum = 0;
    private $attributeNames = array(
        'Age', 'Thickness', 'Proin', 'Sed', 'Duis', 'Quisque', 'Curabitur', 'Suspendisse', 'Nunc'
    );
    private $attributeId = array();
    private $attributeGroupNum = 0;
    private $attributeGroupNames = array(
        'Quality', 'Textile', 'Size', 'Long', 'Male/Female', 'Weight', 'Kids'
    );

    // Manufacturer
    private $manufacturer = array(
        'Adidas', 'Nike', 'Puma', 'Supreme'
    );
    private $manufacturerId = array();

    // Review
    private $reviewState = false;
    private $reviewName = array(
        'Tom', 'Jon', 'Mark', 'Jason', 'Emma', 'Henry', 'Betty', 'Anna'
    );
    private $reviewText = array(
        'Very fast delivery, thank you very much',
        'I liked everything very much, I am grateful',
        'Quality is on top, everything is great',
        'Did not expect the service to be so terrible',
        'I thought I would wait a month. but everything arrived very quickly, thanks',
        'I do not advise anyone to contact this store',
        'There were big problems with delivery, very big problems',
    );
    private $reviewRating = array(
        '1', '2', '3', '4', '5'
    );
    private $reviewNumRand = array(
        '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'
    );



    // File
    private $file;
    private $totalIterations;
    private $currentIteration = 0;

    private $db;

    private $success = false;


    public function __construct($config = array()){
        if(!is_file('config.php')){
            echo $this->error('File config.php does not exist');
            return false;
        }

        //include config file
        include 'config.php';

        ini_set('max_execution_time', 900);

        
        //validate post
        if($this->isPostValid($_POST)) {
            //connect to opencart db
            if(!$this->sql_connect()){
                echo $this->error('Problems connecting to the database');
                return false;
            }else{
                $this->totalIterations = $this->countTotalIterations();
                $this->create();
                $this->success = true;
            }
        }

        echo $this->form();
    }

    private function log($count) {
        $this->currentIteration += $count;
        $loading = round($this->currentIteration / $this->totalIterations * 100);
        $this->file = fopen('iteration.state',"w");
        fwrite($this->file, ($loading > 100 ) ? 100 : $loading);
        fclose($this->file);
    }


    private function create(){
        if($this->numTopCategories){
            $topCategories = $this->createCategories($this->numTopCategories);

            $subCategories = array();
            
            if($topCategories && $this->numSubCategories){
                foreach($topCategories as $category){

                    $subCategories[] = $this->createCategories($this->numSubCategories, $category['category_id']);
                }
            }

            $subCategoriesMas = array();
            foreach ($subCategories as $subCategory){
                foreach ($subCategory as $item) {
                    $subCategoriesMas[] = $item;
                }
            }

            if($topCategories && $this->numProducts){
                if($subCategoriesMas){
                    $topCategories = array_merge($topCategories, $subCategoriesMas);
                }
                $this->createManufacturer();
                $this->createProducts($topCategories);
            }
        }
    }

    private function createCategories($numCategories, $parent_id = 0){
        $sql = "INSERT INTO `".DB_PREFIX."category` 
                  ( `image`, `parent_id`, `top`, `column`, `sort_order`, `status`, `date_added`, `date_modified`) VALUES ";


        $sql_array = array();
        for ($i = 0; $i < $numCategories; $i++) {
            if ($parent_id) {
                $sql_array[] = "('', '".$parent_id."', '0', '0', '1', '1', NOW(), NOW())";
            } else {
                $sql_array[] = "('', '0', '1', '4', '0', '1', NOW(), NOW())";
            }
        }

        $sql .= implode(',', $sql_array);

        if($this->sql_query($sql)){
            $this->log($numCategories);
            if ($parent_id != 0) {
                return $this->createCategoryInfo($this->numSubCategories, $parent_id);
            } else {
                return $this->createCategoryInfo($this->numTopCategories, $parent_id);
            }
        }else{
            return false;
        }
    }

    private function createCategoryInfo($numCategories, $parent_id = 0){

        $sql = "SELECT * FROM `".DB_PREFIX."category` ORDER BY `category_id` DESC LIMIT " .$numCategories;

        $inserted_categories = $this->sql_query($sql);

        $sql_category_description = "INSERT INTO `".DB_PREFIX."category_description` 
                        (`category_id`, `language_id`, `name`, `description`, `meta_title`, `meta_description`, `meta_keyword`) 
                        VALUES ";
        $sql_category_to_store = "INSERT INTO `".DB_PREFIX."category_to_store` (`category_id`, `store_id`) VALUES ";
        $sql_category_path = "INSERT INTO `".DB_PREFIX."category_path` (`category_id`, `path_id`, `level`) VALUES ";


        foreach($inserted_categories as $category){

            $cateogory_name = array_rand ($this->defaultCategories);
            $sql_category_description_array[] = "('". (int)$category['category_id'] ."', '1', '".$this->sql_escape($this->defaultCategories[$cateogory_name])."', '', '".$this->sql_escape($this->defaultCategories[$cateogory_name])."', '', '')";
            $sql_category_to_store_array[] = "('". (int)$category['category_id'] ."', '0')";
            if($parent_id){
                $sql_category_path_array[] = "('".(int)$category['category_id']."', '". (int)$parent_id ."', '1'),
                ('".(int)$category['category_id']."', '". (int)$category['category_id'] ."', '2')";
            }else{
                $sql_category_path_array[] = "('".(int)$category['category_id']."', '". (int)$category['category_id'] ."', '0')";
            }

        }

        $sql_category_description .= implode(',', $sql_category_description_array);
        $sql_category_to_store .= implode(',', $sql_category_to_store_array);
        $sql_category_path .= implode(',', $sql_category_path_array);


        $result['category_description'] = $this->sql_query($sql_category_description);
        $result['category_to_store'] = $this->sql_query($sql_category_to_store);
        $result['category_path'] = $this->sql_query($sql_category_path);

        return $inserted_categories;

    }

    private function createProducts($categories){

        // Create attribute groups
        if ($this->attributeState) {
            $this->createAttributeGroups();
        }

        foreach ($categories as $category) {

            $sql = "INSERT INTO `".DB_PREFIX."product` 
            (`model`, `sku`, `upc`, `ean`, `jan`, `isbn`,`mpn`, `location`, `quantity`, `stock_status_id`, `image`, 
            `manufacturer_id`, `shipping`, `price`, `points`, `tax_class_id`, `date_available`, `weight`, 
            `weight_class_id`, `length`, `width`, `height`, `length_class_id`, `subtract`, `minimum`, `sort_order`, 
            `status`, `viewed`, `date_added`, `date_modified`) VALUES ";

            $image_mas = $this->getLastImage('product_image');

            $sql_array = array();
            for ($i = 0; $i < $this->numProducts; $i++) {
                $image_id = array_rand($image_mas);
                $image = $image_mas[$image_id];

                $manufacturer_id = array_rand($this->manufacturerId);
                $manufacturer = $this->manufacturerId[$manufacturer_id];

                $prod_name = array_rand($this->defaultProducts);
                $price = 1000.0000 + $i * 4 * 18;
                $sql_array[] = "('".$this->defaultProducts[$prod_name]."', '', '', '', '', '', '', '', '100', '9', '".$image."', '".(int)$manufacturer."', '1', '".$price."', '0', '9', '0000-00-00', '5.00000000', '1', '0.00000000', '0.00000000', '0.00000000', '1', '1', '1', '0', '1', '0', NOW(), NOW())";
            }

            $sql .= implode(',', $sql_array);

            if($this->sql_query($sql)){
                $this->log($this->numProducts);
                $result = $this->createProductInfo($category['category_id']);
            }

        }

        return $result;

    }

    private function createProductInfo($category_id) {
        $sql = "SELECT * FROM `".DB_PREFIX."product` ORDER BY `product_id` DESC LIMIT " . $this->numProducts;

        $inserted_products = $this->sql_query($sql);


        $query_product_description = " INSERT INTO `".DB_PREFIX."product_description` (`product_id`, `language_id`, `name`, `description`, `tag`, `meta_title`, `meta_description`, `meta_keyword`) VALUES ";
        $query_product_to_store = " INSERT INTO `".DB_PREFIX."product_to_store` (`product_id`, `store_id`) VALUES ";
        $query_product_image = " INSERT INTO `".DB_PREFIX."product_image` (`product_image_id`, `product_id`, `image`, `sort_order`) VALUES ";
        $query_product_to_category = " INSERT INTO `".DB_PREFIX."product_to_category` (`product_id`, `category_id`) VALUES ";
        $query_product_reward = " INSERT INTO `".DB_PREFIX."product_reward` (`product_reward_id`, `product_id`, `customer_group_id`, `points`) VALUES ";

        if ($this->attributeState) {
            $query_product_attribute = "INSERT INTO `".DB_PREFIX."product_attribute` (`product_id`, `attribute_id`, `language_id`, `text`) VALUES ";
        }

        if ($this->reviewState) {
            $query_review = "INSERT INTO `oc_review` (`product_id`, `customer_id`, `author`, `text`, `rating`, `status`, `date_added`, `date_modified`) VALUES ";
        }

        $image_mas = $this->getLastImage('product_image');

        foreach ($inserted_products as $product_id) {

            $image_id = array_rand($image_mas);
            $image = $image_mas[$image_id];

            if ($this->attributeState) {
                $query_product_attribute_array[] = $this->createProductAttribute($product_id['product_id']);
            }

            if ($this->reviewState) {
                $query_review_array[] = $this->createReviews($product_id['product_id']);
            }

            $prod_name = array_rand($this->defaultProducts);
            $query_product_description_array[] = "('". (int)$product_id['product_id'] ."', '1', '".$this->defaultProducts[$prod_name]."', '". $this->productDescription ."', '', '". $this->defaultProducts[$prod_name] ."', '', '')";
            $query_product_to_store_array[] = "('". (int)$product_id['product_id'] ."', '0')";
            $query_product_image_array[] = "(NULL, '". (int)$product_id['product_id'] ."', '".$image."', '0')";
            $query_product_to_category_array[] = "('". (int)$product_id['product_id'] ."', '". (int)$category_id ."')";
            $query_product_reward_array[] = "(NULL, '". (int)$product_id['product_id'] ."', '1', '0')";

        }


        $query_product_description .= implode(',', $query_product_description_array);
        $query_product_to_store .= implode(',', $query_product_to_store_array);
        $query_product_image .= implode(',', $query_product_image_array);
        $query_product_to_category .= implode(',', $query_product_to_category_array);
        $query_product_reward .= implode(',', $query_product_reward_array);
        if ($this->attributeState) {
            $query_product_attribute .= implode(',', $query_product_attribute_array);
        }
        if ($this->reviewState) {
            $query_review .= implode(',', $query_review_array);
        }

        $result['product_description'] = $this->sql_query($query_product_description);
        $result['product_to_store'] = $this->sql_query($query_product_to_store);
        $result['product_image'] = $this->sql_query($query_product_image);
        $result['product_to_category'] = $this->sql_query($query_product_to_category);
        $result['product_reward'] = $this->sql_query($query_product_reward);
        if ($this->attributeState) {
            $result['product_attribute'] = $this->sql_query($query_product_attribute);
        }
        if ($this->reviewState) {
            $result['product_review'] = $this->sql_query($query_review);
        }

        return $inserted_products;

    }

    // Create attribute group
    private function createAttributeGroups() {

        $sql = "INSERT INTO `".DB_PREFIX."attribute_group` (`sort_order`) VALUES ";
        $query_attribute_group = array();

        for ($i = 0; $i < $this->attributeGroupNum; $i++) {
            $query_attribute_group[] = "('1')";
        }
        $sql .= implode(',', $query_attribute_group);
        $this->sql_query($sql);

        // Get id of attributes group
        $sql = "SELECT * FROM `".DB_PREFIX."attribute_group` ORDER BY `attribute_group_id` DESC LIMIT ". $this->attributeGroupNum;
        $added_id = $this->sql_query($sql);

        $sql = "INSERT INTO `".DB_PREFIX."attribute_group_description` (`attribute_group_id`, `language_id`, `name`) VALUES ";
        $query_attribute_group_description = array();

        foreach ($added_id as $id) {
            $name = array_rand($this->attributeGroupNames);
            $query_attribute_group_description[] = "('".(int)$id['attribute_group_id']."', '1', '".$this->attributeGroupNames[$name]."')";
        }

        $sql .= implode(',', $query_attribute_group_description);
        $result = $this->sql_query($sql);

        $this->createAttribute($added_id);


    }

    // Create attribute
    private function createAttribute($attributeGroupId) {

        $sql = "INSERT INTO `".DB_PREFIX."attribute` (`attribute_group_id`, `sort_order`) VALUES ";

        $sql_attribute = array();

        foreach ($attributeGroupId as $group_id) {
            for ($i = 0; $i < $this->attributeNum; $i++) {
                $sql_attribute[] = "('".$group_id['attribute_group_id']."', '1')";
            }
        }

        $sql .= implode(',', $sql_attribute);
        $this->sql_query($sql);

        $sql = "SELECT * FROM `".DB_PREFIX."attribute` ORDER BY `attribute_id` DESC LIMIT ". $this->attributeNum * $this->attributeGroupNum;
        $added_id = $this->sql_query($sql);

        $sql = "INSERT INTO `".DB_PREFIX."attribute_description` (`attribute_id`, `language_id`, `name`) VALUES ";

        $sql_attribute_description = array();

        foreach ($added_id as $id) {
            $name = array_rand($this->attributeNames);
            $sql_attribute_description[] = "('".$id['attribute_id']."', '1', '".$this->attributeNames[$name]."')";
        }

        $sql .= implode(',', $sql_attribute_description);
        $this->sql_query($sql);

        $this->attributeId = $added_id;

    }

    // Binds attributes to products
    private function createProductAttribute($productId) {

        foreach ($this->attributeId as $attributeId) {
            $name_id = array_rand($this->defaultProducts);

            $query_product_attribute_array[] = "('".$productId."', '".$attributeId['attribute_id']."', '1', '".$this->defaultProducts[$name_id]."')";

        }

        $query_product_attribute = implode(',', $query_product_attribute_array);

        return $query_product_attribute;

    }

    // Create four manufacturer
    private function createManufacturer() {

        $sql = "INSERT INTO `".DB_PREFIX."manufacturer` (`name`, `image`, `sort_order`) VALUES ";

        $image_mas = $this->getLastImage('manufacturer');

        for ($i = 0; $i < 4; $i++) {
            $name_id = array_rand($this->manufacturer);
            $image_id = array_rand($image_mas);
            $query_manufacturer[] = "('".$this->manufacturer[$name_id]."', '".$image_mas[$image_id]."', '1')";
        }

        $sql .= implode(',', $query_manufacturer);
        $this->sql_query($sql);

        $inserted_id = $this->sql_query("SELECT * FROM `".DB_PREFIX."manufacturer` ORDER BY `manufacturer_id` DESC LIMIT 4");

        $sql = "INSERT INTO `oc_manufacturer_to_store` (`manufacturer_id`, `store_id`) VALUES ";

        foreach ($inserted_id as $manufacturer_id) {
            $this->manufactorId[] = $manufacturer_id['manufacturer_id'];
            $query[] = "('".$manufacturer_id['manufacturer_id']."', '0')";
        }

        $sql .= implode(',', $query);

        return $this->sql_query($sql);

    }

    // Create product reviews
    private function createReviews($productId) {

        $reviewNum = array_rand($this->reviewNumRand);
        $reviewNumber = $this->reviewNumRand[$reviewNum];

        for ($i = 0; $i < $reviewNumber; $i++) {
            $name_id = array_rand($this->reviewName);
            $text_id = array_rand($this->reviewText);
            $rating_id = array_rand($this->reviewRating);
            $query[] = "('".$productId."', '0', '".$this->reviewName[$name_id]."', '".$this->reviewText[$text_id]."', '".$this->reviewRating[$rating_id]."', '1', NOW(), NOW())";

        }

        $sql = implode(',', $query);

        return $sql;

    }

    // Get mas of images from current table
    private function getLastImage($tableName) {
        $sql_image = "SELECT `image` FROM `".DB_PREFIX.$tableName."` ORDER BY `image`";
        $images = $this->sql_query($sql_image);
        $image_mas = array();
        foreach ($images as $image) {
            $image_mas[] = $image['image'];
        }

        return $image_mas;
    }

    private function countTotalIterations() {
        $categories = $this->numTopCategories * $this->numSubCategories + $this->numTopCategories;
        $result = $categories * $this->numProducts;
        return $result;
    }

    private function sql_connect()
    {
        $this->db = mysqli_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
        if (mysqli_connect_errno()) {
            return false;
        }
        return true;
    }

    private function sql_query($sql)
    {
        $query = mysqli_query($this->db,$sql);

        if($query){
            if($query instanceof mysqli_result){

                while ($row = mysqli_fetch_assoc($query)) {
                    $data[] = $row;
                }
                return $data;
            }else {
                return true;
            }
        }else{
            return false;
        }
    }

    private function sql_escape($data){
        return mysqli_real_escape_string($this->db, $data);
    }

    private function isPostValid($post){

        if (isset($post['category']) && isset($post['subcategory']) && isset($post['product'])) {
            if ($post['category'] != '' && $post['subcategory'] != '' && $post['product'] != '') {
                $this->numTopCategories = (int)$post['category'];
                $this->numSubCategories = (int)$post['subcategory'];
                $this->numProducts = (int)$post['product'];
                $this->attributeGroupNum = (int)$post['attributeGroup'];
                $this->attributeNum = (int)$post['attribute'];
                if ($post['reviews'] == "on") {
                    $this->reviewState = true;
                }

                if (!$this->attributeGroupNum == '' && !$this->attributeNum == '') {
                    $this->attributeState = true;
                }

                return true;
            } else {
                return false;
            }

        } else {


            return false;

        }

    }


    public function form() {

        $html = '<!doctype html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport"
                  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <link href="https://dreamvention.github.io/RipeCSS/css/ripe.css" rel="stylesheet">
            <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
            <title>Products Generator -Opencart</title>
        </head>
        <body>
        <style type="text/css">
            body{background-color:#F6F9FC;}
            a:hover{text-decoration:none;}
        </style>

            <div style="text-align: center; margin-top: 80px"><a href="https://shopunity.net">
                    <img src="https://shopunity.net/catalog/view/theme/default/image/logo.png"></a></div>
            <div class="row">
                <div></div>

                <div class="ve-col-6" style="margin: 30px auto">
                    <div class="ve-card">
                        <div class="ve-card__section">
                            <h2 class="ve-h2">Product Generator</h2>
                            <p class="ve-p">Add as many categories, subcategories and products as you need to the
                                Opencart database!</p>
                        </div>
                        <hr class="ve-hr"/>
                        <div class="ve-card__section">

                            <form id="pg_form">';
        if($this->success){
            $html .=           '<div class="ve-alert ve-alert--success ve-alert--dismissible">
                                    <h4 class="ve-h4 ve-alert__heading">Request Successful</h4>
                                </div>';
        }
        $html .=                '<div class="ve-field">
                                    <label class="ve-label" for="categories">Number of categories:</label>
                                    <input type="text" class="ve-input" id="categories" name="category"/>
                                    <small class="form-text text-muted">Here you must indicate the total number of parent categories.</small>
                                </div>
                                <div class="ve-field">
                                    <label class="ve-label" for="subcategories"">Number of subcategories:</label>
                                    <input type="text" class="ve-input"  id="subcategories" name="subcategory"/>
                                    <small class="form-text text-muted">Here specify how many subcategories you want in each parent category.</small>
                                </div>
                                <div class="ve-field">
                                    <label class="ve-label" for="product">Number of products:</label>
                                    <input type="text" class="ve-input" id="product" name="product"/>
                                    <small class="form-text text-muted">Here you must specify the number of products you want to add to one category.<br>
                                    For example: if the number of categories is 2, the number of subcategories is 3 and the quantity of products is 5, then in total you will have 40 products.</small>
                                </div>
                                <div class="ve-field">
                                    <label class="ve-label" for="attributeGroup">Number of attribute groups:</label>
                                    <input type="text" class="ve-input" id="attributeGroup" name="attributeGroup"/>
                                    <small class="form-text text-muted">Here you must specify the total number of product attribute groups.</small>
                                </div>
                                <div class="ve-field">
                                    <label class="ve-label" for="attribute">Number of attributes:</label>
                                    <input type="text" class="ve-input" id="attribute" name="attribute"/>
                                    <small class="form-text text-muted">Here you must specify the number of attributes you want to add to each attribute group<br>
                                    For example: if the number of attribute groups - 3 and the number of attributes - 2, then in total you will have 6 attributes for each product</small>
                                </div>
                                <div style="margin: 20px 0 20px 0">  
                                    <label class="ve-checkbox">
                                        <input type="checkbox" name="reviews" />
                                        <i></i> Create product reviews
                                    </label>
                                    <small class="form-text text-muted">Here you can create reviews and ratings for each product.</small>
                                </div>

                            </form>
                            
                            <div id="pg_submit" class="ve-btn ve-btn--primary ve-col-12 ve-btn--lg pg_submit">Send</div>

                        </div>
                    </div>
                    <div class="footer ve-col-12" style="margin: 80px auto 0 auto; color: #929292; text-align: center;">
                        <hr class="ve-hr">
                        <div style="margin: 10px 0 5px 0">ProductGenerator version:  <span style="margin-left: 5px">'.$this->PGversion .'</span></div>
                        <div style="margin-bottom: 0">Powered by <a class="link-shopunity" href="https://shopunity.net">Shopunity.net</a></div>
                    </div>
                </div>
            </div>
            <script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script>
            <script>
                var timer = null;
                var form_block = document.getElementById("pg_form");
                $(document).on("click", "#pg_submit", function(){
                	var data = $("#pg_form").serialize();
                	$.post("ProductGenerator.php", data);
                	var loading = 0;
                	$("#pg_submit").text("Loading "+loading+"%");
                	form_block.style.opacity = "0.5";
                	timer = setInterval(fetchLog, 4000);
                	return false;
                });
                
                function fetchLog() {
                    $.ajax({
                        type:    "GET",
                        url:     "'.HTTPS_SERVER.'iteration.state",
                        success: function(data) {
                            var loading = data;
                            $("#pg_submit").text("Loading "+loading+"%");
                            if(loading == 100){
                            	form_block.style.opacity = "1";
                            	form_block.reset();
                                clearInterval(timer);
                                $("#pg_submit").text("100% - Successful");
                                setTimeout(changeBut, 1500);
                                function changeBut() {
                                    $("#pg_submit").text("Send");
                                }
                            } 
                        },
                        error:   function() {
                        
                        }
                    });
                  }
            	
            </script>

        </body>
        </html>';

        return $html;

    }

    public function error($message = 'An error occurred while starting the generator!'){
        $html = '<!doctype html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport"
                  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <link href="https://dreamvention.github.io/RipeCSS/css/ripe.css" rel="stylesheet">
            <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
            <title>Products Generator -Opencart</title>
        </head>
        <body>
        <style type="text/css">
            body {
                background-color: #F6F9FC;
            }

            a:hover {
                text-decoration: none;
            }

        </style>

            <div style="text-align: center; margin-top: 100px"><a href="https://shopunity.net">
                    <img src="https://shopunity.net/catalog/view/theme/default/image/logo.png"></a></div>
            <div class="row">
                <div></div>

                <div class="ve-col-4" style="margin: 30px auto">
                    <div class="ve-card">
                        <div class="ve-card__section">
                            <h2 class="ve-h2">Product Generator</h2>
                            <p class="ve-p">Add as many categories, subcategories and products as you need to the
                                Opencart database!</p>
                        </div>
                        <hr class="ve-hr"/>
                        <div class="ve-card__section">

                            <div class="ve-alert ve-alert--danger ve-alert--dismissible"><h5>'.$message.'</h5></div>

                        </div>
                    </div>
                </div>
                <div class="footer ve-col-12" style="margin: 80px auto 0 auto; color: #929292; text-align: center;">
                    <hr class="ve-hr">
                    <div style="margin: 10px 0 5px 0">ProductGenerator version:  <span style="margin-left: 5px">'.$this->PGversion .'</span></div>
                    <div style="margin-bottom: 0">Powered by <a class="link-shopunity" href="https://shopunity.net">Shopunity.net</a></div>
                </div>
            </div>

        </body>
        </html>';

        return $html;
    }

}


