<?php
class Book {
    private $conn;
    private $table = 'books';

    public $id;
    public $title;
    public $author;
    public $isbn;
    public $publication_year;
    public $genre;
    public $description;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all books
    public function read() {
        $query = 'SELECT * FROM ' . $this->table . ' ORDER BY id DESC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get single book
    public function readSingle() {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE id = ? LIMIT 0,1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->title = $row['title'];
        $this->author = $row['author'];
        $this->isbn = $row['isbn'];
        $this->publication_year = $row['publication_year'];
        $this->genre = $row['genre'];
        $this->description = $row['description'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
    }

    // Create book
    public function create() {
        $query = 'INSERT INTO ' . $this->table . ' SET title=:title, author=:author, isbn=:isbn, publication_year=:publication_year, genre=:genre, description=:description';
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->title=htmlspecialchars(strip_tags($this->title));
        $this->author=htmlspecialchars(strip_tags($this->author));
        $this->isbn=htmlspecialchars(strip_tags($this->isbn));
        $this->publication_year=htmlspecialchars(strip_tags($this->publication_year));
        $this->genre=htmlspecialchars(strip_tags($this->genre));
        $this->description=htmlspecialchars(strip_tags($this->description));

        // Bind data
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':author', $this->author);
        $stmt->bindParam(':isbn', $this->isbn);
        $stmt->bindParam(':publication_year', $this->publication_year);
        $stmt->bindParam(':genre', $this->genre);
        $stmt->bindParam(':description', $this->description);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update book
    public function update() {
        $query = 'UPDATE ' . $this->table . ' SET title=:title, author=:author, isbn=:isbn, publication_year=:publication_year, genre=:genre, description=:description WHERE id = :id';
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->title=htmlspecialchars(strip_tags($this->title));
        $this->author=htmlspecialchars(strip_tags($this->author));
        $this->isbn=htmlspecialchars(strip_tags($this->isbn));
        $this->publication_year=htmlspecialchars(strip_tags($this->publication_year));
        $this->genre=htmlspecialchars(strip_tags($this->genre));
        $this->description=htmlspecialchars(strip_tags($this->description));
        $this->id=htmlspecialchars(strip_tags($this->id));

        // Bind data
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':author', $this->author);
        $stmt->bindParam(':isbn', $this->isbn);
        $stmt->bindParam(':publication_year', $this->publication_year);
        $stmt->bindParam(':genre', $this->genre);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete book
    public function delete() {
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = ?';
        $stmt = $this->conn->prepare($query);
        $this->id=htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>