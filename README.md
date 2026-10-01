# Book Library CRUD Application

A simple MVP book library application built with:
- **Backend**: PHP RESTful API served by FrankenPHP
- **Database**: MySQL
- **Frontend**: HTML/CSS/JavaScript with Axios for API calls

## Features
- View all books
- Add new books
- Edit existing books
- Delete books
- Responsive design

## Project Structure
```
Ecomerce-crud/
├── api/                 # PHP API controllers and models
│   ├── controllers/     # API endpoints
│   ├── models/          # Database models
│   └── utils/           # Utility classes (database connection)
├── public/              # Frontend assets
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── index.html       # Main HTML page
├── db/                  # Database schema
│   └── schema.sql       # SQL schema and sample data
├── docker/              # Docker configuration
│   └── frankenphp/      # FrankenPHP server config
├── docker-compose.yml   # Docker Compose file
└── README.md            # This file
```

## Setup Instructions

### Prerequisites
- Docker and Docker Compose installed
- Ports 8080 and 3306 available

### Installation
1. Clone this repository
2. Navigate to the project directory
3. Start the application with Docker Compose:
   ```bash
   docker-compose up -d
   ```

### Access the Application
- **Frontend**: http://localhost:8080
- **API Endpoint**: http://localhost:8080/api/controllers/BooksController.php
- **MySQL**: localhost:3306 (username: user, password: password, database: book_library)

## API Endpoints

| Method | Endpoint                           | Description          |
|--------|------------------------------------|----------------------|
| GET    | /api/controllers/BooksController.php | Get all books        |
| GET    | /api/controllers/BooksController.php/{id} | Get single book    |
| POST   | /api/controllers/BooksController.php | Create new book      |
| PUT    | /api/controllers/BooksController.php | Update existing book |
| DELETE | /api/controllers/BooksController.php | Delete book          |

## Sample Data
The application comes with pre-loaded sample data including:
- The Great Gatsby by F. Scott Fitzgerald
- To Kill a Mockingbird by Harper Lee
- 1984 by George Orwell
- Pride and Prejudice by Jane Austen
- The Hobbit by J.R.R. Tolkien

## Development Notes
- The frontend uses vanilla JavaScript with Axios for HTTP requests
- CORS is enabled on the API to allow cross-origin requests
- FrankenPHP serves both the static frontend files and the PHP API
- The application uses a MySQL database initialized with the schema.sql file

## Troubleshooting
- If the API doesn't respond, check if the FrankenPHP container is running: `docker-compose ps`
- View logs: `docker-compose logs frankenphp`
- Restart services: `docker-compose restart`
- Rebuild containers: `docker-compose up --build -d`

## License
MIT License