<?php 

//----------------------------common start----------------------------------

function alert($data,$color="danger"){
    return "<p class='alert alert-$color'>$data</p>";
}

function runQuery($sql){
    $conn = con();
    if(mysqli_query($conn,$sql)){
        return true;
    }else{
        die("Query Fail: ".mysqli_error($conn));
    }
}

function redirect($l){
    header("location:$l");
}

function linkTo($l){
    echo "<script>location.href='$l'</script>";
}

function fetch($sql){
    $query = mysqli_query(con(), $sql);
    $row = mysqli_fetch_assoc($query);
    return $row;
}

function fetchAll($sql){
    $query = mysqli_query(con(), $sql);
    $rows = [];
    while($row = mysqli_fetch_assoc($query)){
        array_push($rows,$row);
    }
    return $rows;
}

function showTime($timestamp,$format = "d-m-y"){
    return date($format,strtotime($timestamp));
}

function countTotal($table,$condtion = 1){
    $sql = "SELECT COUNT(id) FROM $table WHERE $condtion";
    $total = fetch($sql);
    return $total['COUNT(id)'];
}

function short($str,$length="50"){
    return substr($str,0,$length)."...";
}

function textFilter($text){
    $text = trim($text);
    $text = htmlentities($text,ENT_QUOTES);
    $text = stripcslashes($text);
    return $text;
}
//----------------------------common end----------------------------------
//----------------------------auth start----------------------------------

function register(){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    if($password == $confirm_password){
        $secure_password = password_hash($password,PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name,email,password) VALUES ('$name','$email','$secure_password')";
        if(runQuery($sql)){
            redirect("login.php");
        }
    }else{
        return alert("Password don't match!");
    }
}


function login(){
    $email = $_POST['email'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE email='$email'";
    $query = mysqli_query(con(),$sql);
    $row = mysqli_fetch_assoc($query);
    if(!$row){
        return alert("Email and Password don't match!");
    }else{
        if(!password_verify($password,$row['password'])){
            return alert("Email or Password don't match");
        }else{
            session_start();
            $_SESSION['user']=$row;
            redirect('dashboard.php');
        }
    }
 
}

//----------------------------auth end---------------------------------------
//----------------------------user start-------------------------------------

function user($id){
    $sql = "SELECT * FROM users WHERE id = $id";
    return fetch($sql);
}

function users(){
    $sql = "SELECT * FROM users";
    return fetchAll($sql); 
}

//----------------------------user end----------------------------------------
//----------------------------category start----------------------------------

function categoryAdd(){
    $title = $_POST['title'];
    $user_id = $_SESSION['user']['id'];
    $sql = "INSERT INTO categories (title,user_id) VALUES ('$title','$user_id')";
    if(runQuery($sql)){
        linkTo("category_add.php");
    }
}

function category($id){
    $sql = "SELECT * FROM categories WHERE id = $id";
    return fetch($sql);
}

function categories(){
    $sql = "SELECT * FROM categories ORDER BY ordering DESC";
    return fetchAll($sql);
}

function categoryDelete($id){
    $sql = "DELETE FROM categories WHERE id = $id";
    return runQuery($sql);
}

function categoryUpdate(){
    $title = $_POST['title'];
    $id = $_POST['id'];
    $sql = "UPDATE categories SET title='$title' WHERE id=$id";
    return runQuery($sql);
}

function categoryPinToTop($id){
    $sql = "UPDATE categories SET ordering = '0'";//all to 0
    mysqli_query(con(),$sql);
    $sql = "UPDATE categories SET ordering = '1' WHERE id=$id";//id to 1
    return runQuery($sql);
}

function categoryRemovePin(){
    $sql = "UPDATE categories SET ordering = '0'";//all to 0
    return runQuery($sql);
}

//----------------------------category end----------------------------------
//----------------------------post start------------------------------------

function postAdd(){
    $title = textFilter($_POST['title']);
    $description = textFilter($_POST['description']);
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user']['id'];
    $sql = "INSERT INTO posts (title,description,category_id,user_id) VALUES ('$title','$description','$category_id','$user_id')";
    if(runQuery($sql)){
        linkTo("post_add.php");
    }
}

function post($id){
    $sql = "SELECT * FROM posts WHERE id = $id";
    return fetch($sql);
}

function posts($limit=9999999){
    if($_SESSION['user']['role'] ==2 ){
        $current_user_id = $_SESSION['user']['id'];
        $sql = "SELECT * FROM posts WHERE user_id = '$current_user_id' LIMIT $limit";//user
    }else{
        $sql = "SELECT * FROM posts LIMIT $limit";
    }
    return fetchAll($sql);
}

function postDelete($id){
    $sql = "DELETE FROM posts WHERE id = $id";
    return runQuery($sql);
}

function postUpdate(){
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $id = $_POST['id'];
    $sql = "UPDATE posts SET title='$title',description='$description',category_id='$category_id' WHERE id=$id";
    return runQuery($sql);
}

//----------------------------post end-------------------------------------------
//----------------------------front panel start----------------------------------

function fCategories(){
    $sql = "SELECT * FROM categories ORDER BY ordering DESC";
    return fetchAll($sql);
}

function fPosts($orderCol="id",$orderType="DESC"){
    $sql = "SELECT * FROM posts ORDER BY $orderCol $orderType";
    return fetchAll($sql);
}

function fPostsByCat($category_id,$limit="99999",$post_id = 0){
    $sql = "SELECT * FROM posts WHERE category_id = $category_id AND id != $post_id ORDER BY id DESC LIMIT $limit";
    return fetchAll($sql);
}

function fSearch($search_key){
    $sql = "SELECT * FROM posts WHERE title LIKE '%$search_key%' OR description LIKE '%$search_key%' ORDER BY id DESC";
    return fetchAll($sql);
}

function fSearchByDate($start,$end){
    $sql = "SELECT * FROM posts WHERE created_at BETWEEN '$start' AND '$end' ORDER BY id DESC";
    return fetchAll($sql);
}

//----------------------------front panel end-------------------------------------
//-----------------------------viewer count start---------------------------------

function viewerRecord($user_id,$post_id,$device){
    $sql = "INSERT INTO viewers (user_id,post_id,device) VALUES ('$user_id','$post_id','$device')";
    runQuery($sql);
}

function viewerCountByPost($post_id){
    $sql = "SELECT * FROM viewers WHERE post_id = $post_id";
    return fetchAll($sql);
}

function viewerCountByUser($user_id){
    $sql = "SELECT * FROM viewers WHERE user_id = $user_id";
    return fetchAll($sql);
}

//----------------------------viewer count end---------------------------------
//----------------------------ads start----------------------------------------

function ads(){
    $today = date("Y-m-d");
    $sql = "SELECT * FROM ads WHERE start <= '$today' AND end > '$today'";
    return fetchAll($sql);
}

//----------------------------ads end-------------------------------------------
//----------------------------Payment start-------------------------------------------
function payNow(){
    $from = $_SESSION['user']['id'];
    $to = $_POST['to_user'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    //from user money update (-)
    $fromUserDetail = user($from);
    $leftMoney = $fromUserDetail['money'] - $amount;
    if($fromUserDetail['money'] >= $amount){
        $sql = "UPDATE users SET money = $leftMoney WHERE id = $from";
        mysqli_query(con(),$sql);// runQuery() ကိုသုံးရင် return ပြန်မာဖစ်တဲ့အတွက်ကြောင့် mysqli_query() ကိုဘဲ သုံးရန်
    
        //to user money update (+)
        $toUserDetail = user($to);
        $newAmount = $toUserDetail['money'] + $amount;
        $sql = "UPDATE users SET money = $newAmount WHERE id = $to";
        mysqli_query(con(),$sql);
    
        //add to transition table
        $sql = "INSERT INTO transition (from_user,to_user,amount,description) VALUES ('$from','$to','$amount','$description')";
        runQuery($sql);
    }
}

function transition($id){
    $sql = "SELECT * FROM transition WHERE id = $id";
    return fetch($sql);
}

function transitions(){
    $user_id = $_SESSION['user']['id'];
    if($_SESSION['user']['role'] == 0){
        $sql = "SELECT * FROM transition";
    }else{
        $sql = "SELECT * FROM transition WHERE from_user = $user_id OR to_user = $user_id";
    }
    return fetchAll($sql);
}
//----------------------------Payment end-------------------------------------------
//----------------------------dashboard start-------------------------------------------

function dashboard_posts($limit=9999999){
    if($_SESSION['user']['role'] ==2 ){
        $current_user_id = $_SESSION['user']['id'];
        $sql = "SELECT * FROM posts WHERE user_id = '$current_user_id' ORDER BY id DESC LIMIT $limit";//user
    }else{
        $sql = "SELECT * FROM posts ORDER BY id DESC LIMIT $limit";
    }
    return fetchAll($sql);
}

//----------------------------dashboard end-------------------------------------------