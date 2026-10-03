// Book Library CRUD Application using Axios

class BookApp {
    constructor() {
        this.apiBaseUrl = 'http://localhost/api/controllers/BooksController.php';
        this.booksList = document.getElementById('booksList');
        this.addBookForm = document.getElementById('addBookForm');

        this.init();
    }

    init() {
        this.loadBooks();
        this.addBookForm.addEventListener('submit', (e) => this.handleAddBook(e));
    }

    async loadBooks() {
        try {
            this.showLoading();
            const response = await axios.get(this.apiBaseUrl);

            if (response.data.data && response.data.data.length > 0) {
                this.renderBooks(response.data.data);
            } else {
                this.booksList.innerHTML = '<p class="empty-state">No books found. Add a new book to get started!</p>';
            }
        } catch (error) {
            console.error('Error loading books:', error);
            this.booksList.innerHTML = '<p class="error">Error loading books. Please try again later.</p>';
        }
    }

    async handleAddBook(e) {
        e.preventDefault();

        const formData = {
            title: document.getElementById('title').value.trim(),
            author: document.getElementById('author').value.trim(),
            isbn: document.getElementById('isbn').value.trim() || null,
            publication_year: document.getElementById('publication_year').value.trim() || null,
            genre: document.getElementById('genre').value.trim() || null,
            description: document.getElementById('description').value.trim() || null
        };

        // Basic validation
        if (!formData.title || !formData.author) {
            alert('Please fill in required fields (Title and Author)');
            return;
        }

        try {
            const response = await axios.post(this.apiBaseUrl, formData);

            if (response.data.message) {
                alert(response.data.message);
                this.addBookForm.reset();
                this.loadBooks(); // Refresh book list
            }
        } catch (error) {
            console.error('Error adding book:', error);
            alert('Error adding book. Please try again.');
        }
    }

    async handleUpdateBook(id) {
        // Find the book card to get current values
        const bookCard = document.querySelector(`.book-card[data-id="${id}"]`);
        if (!bookCard) return;

        const title = bookCard.querySelector('[data-field="title"]').textContent;
        const author = bookCard.querySelector('[data-field="author"]').textContent;
        const isbn = bookCard.querySelector('[data-field="isbn"]').textContent;
        const publication_year = bookCard.querySelector('[data-field="publication_year"]').textContent;
        const genre = bookCard.querySelector('[data-field="genre"]').textContent;
        const description = bookCard.querySelector('[data-field="description"]').textContent;

        // Prompt for new values
        const newTitle = prompt('Enter new title:', title) || title;
        const newAuthor = prompt('Enter new author:', author) || author;
        const newIsbn = prompt('Enter new ISBN:', isbn) || isbn;
        const newPublicationYear = prompt('Enter new publication year:', publication_year) || publication_year;
        const newGenre = prompt('Enter new genre:', genre) || genre;
        const newDescription = prompt('Enter new description:', description) || description;

        const updateData = {
            id: id,
            title: newTitle,
            author: newAuthor,
            isbn: newIsbn,
            publication_year: newPublicationYear,
            genre: newGenre,
            description: newDescription
        };

        try {
            const response = await axios.put(this.apiBaseUrl, updateData);

            if (response.data.message) {
                alert(response.data.message);
                this.loadBooks(); // Refresh book list
            }
        } catch (error) {
            console.error('Error updating book:', error);
            alert('Error updating book. Please try again.');
        }
    }

    async handleDeleteBook(id) {
        if (!confirm('Are you sure you want to delete this book?')) {
            return;
        }

        try {
            const response = await axios.delete(this.apiBaseUrl, {
                data: { id: id }
            });

            if (response.data.message) {
                alert(response.data.message);
                this.loadBooks(); // Refresh book list
            }
        } catch (error) {
            console.error('Error deleting book:', error);
            alert('Error deleting book. Please try again.');
        }
    }

    renderBooks(books) {
        if (books.length === 0) {
            this.booksList.innerHTML = '<p class="empty-state">No books found.</p>';
            return;
        }

        this.booksList.innerHTML = books.map(book => `
            <div class="book-card" data-id="${book.id}">
                <h3>${book.title}</h3>
                <p><strong>Author:</strong> <span data-field="author">${book.author}</span></p>
                <p><strong>ISBN:</strong> <span data-field="isbn">${book.isbn || 'N/A'}</span></p>
                <p><strong>Year:</strong> <span data-field="publication_year">${book.publication_year || 'N/A'}</span></p>
                <p><strong>Genre:</strong> <span data-field="genre">${book.genre || 'N/A'}</span></p>
                <p><strong>Description:</strong> <span data-field="description">${book.description || 'No description available'}</span></p>
                <div class="book-actions">
                    <button class="btn-info" onclick="app.handleUpdateBook(${book.id})">Edit</button>
                    <button class="btn-danger" onclick="app.handleDeleteBook(${book.id})">Delete</button>
                </div>
            </div>
        `).join('');
    }

    showLoading() {
        this.booksList.innerHTML = '<p class="loading">Loading books...</p>';
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new BookApp();
});