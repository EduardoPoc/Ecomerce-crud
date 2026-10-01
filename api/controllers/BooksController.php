<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../api/utils/database.php';
require_once '../../api/models/Book.php';

$database = new Database();
$db = $database->getConnection();

$book = new Book($db);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));

switch($method) {
    case 'GET':
        if (!empty($request[0]) && is_numeric($request[0])) {
            // GET single book
            $book->id = $request[0];
            $book->readSingle();
            if($book->title){
                http_response_code(200);
                echo json_encode(array(
                    "id" => $book->id,
                    "title" => $book->title,
                    "author" => $book->author,
                    "isbn" => $book->isbn,
                    "publication_year" => $book->publication_year,
                    "genre" => $book->genre,
                    "description" => $book->description,
                    "created_at" => $book->created_at,
                    "updated_at" => $book->updated_at
                ));
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Book not found"));
            }
        } else {
            // GET all books
            $stmt = $book->read();
            $num = $stmt->rowCount();

            if($num > 0){
                $books_arr=array();
                $books_arr["data"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $book_item=array(
                        "id" => $id,
                        "title" => $title,
                        "author" => $author,
                        "isbn" => $isbn,
                        "publication_year" => $publication_year,
                        "genre" => $genre,
                        "description" => $description,
                        "created_at" => $created_at,
                        "updated_at" => $updated_at
                    );
                    array_push($books_arr["data"], $book_item);
                }

                http_response_code(200);
                echo json_encode($books_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "No books found"));
            }
        }
        break;

    case 'POST':
        // Create book
        $data = json_decode(file_get_contents("php://input"));

        $book->title = $data->title;
        $book->author = $data->author;
        $book->isbn = $data->isbn;
        $book->publication_year = $data->publication_year;
        $book->genre = $data->genre;
        $book->description = $data->description;

        if(!empty($book->title) && !empty($book->author)){
            if($book->create()){
                http_response_code(201);
                echo json_encode(array("message" => "Book was created"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to create book"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to create book. Data is incomplete."));
        }
        break;

    case 'PUT':
        // Update book
        $data = json_decode(file_get_contents("php://input"));

        $book->id = $data->id;
        $book->title = $data->title;
        $book->author = $data->author;
        $book->isbn = $data->isbn;
        $book->publication_year = $data->publication_year;
        $book->genre = $data->genre;
        $book->description = $data->description;

        if(!empty($book->title) && !empty($book->author) && !empty($book->id)){
            if($book->update()){
                http_response_code(200);
                echo json_encode(array("message" => "Book was updated"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to update book"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to update book. Data is incomplete."));
        }
        break;

    case 'DELETE':
        // Delete book
        $data = json_decode(file_get_contents("php://input"));

        $book->id = $data->id;

        if(!empty($book->id)){
            if($book->delete()){
                http_response_code(200);
                echo json_encode(array("message" => "Book deleted"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to delete book"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to delete book"));
        }
        break;

    case 'OPTIONS':
        http_response_code(200);
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed"));
        break;
}
?>