package main

import (
	"database/sql"
	"errors"
	"fmt"
	"net/http"
	"strings"
)

type Book struct {
	ID      int64
	Title   string
	Year    int
	ISBN    string
	Authors []Author
}

func (b Book) String() string {
	return b.Title
}

func ScanBook(row Scanable, queryAuthor bool) (Book, error) {
	var book Book
	err := row.Scan(&book.ID, &book.Title, &book.Year, &book.ISBN)

	if queryAuthor && err == nil {
		book.Authors, err = queryAuthorsByBook(db, book.ID)
	}

	return book, err
}

func ScanBookRows(rows *sql.Rows, queryAuthor bool) ([]Book, error) {
	var books []Book
	var err error

	for rows.Next() {
		book, err := ScanBook(rows, queryAuthor)

		if err != nil {
			break
		}

		books = append(books, book)
	}
	return books, err
}

type BookController struct{}

func (BookController) Parse(r *http.Request, id int64) Book {
	book := Book{
		ID:    id,
		Title: r.FormValue("title"),
		Year:  int(atoi(r.FormValue("year"), 0)),
		ISBN:  r.FormValue("isbn"),
	}

	authors := r.FormValue("authors")
	for _, name := range strings.Split(authors, ";") {
		name := strings.TrimSpace(name)
		book.Authors = append(book.Authors, Author{Name: name})
	}
	return book
}

func InsertBookAuthors(db *sql.DB, bookID int64, authors []Author) error {
	stmtAuthor, _ := db.Prepare("INSERT INTO authors (name) VALUES (?)")
	stmtRel, _ := db.Prepare("INSERT INTO book_authors (book, author) VALUES (?, ?)")

	for _, a := range authors {
		author, err := queryAuthorByName(db, a.Name)

		authorID := author.ID
		if err != nil {
			result, _ := stmtAuthor.Exec(a.Name)
			authorID, _ = result.LastInsertId()
		}

		stmtRel.Exec(bookID, authorID)
	}

	return nil
}

func (BookController) Insert(db *sql.DB, book Book) error {
	stmt, _ := db.Prepare("INSERT INTO books (title, year, isbn) VALUES (?, ?, ?)")
	result, _ := stmt.Exec(book.Title, book.Year, book.ISBN)

	bookID, _ := result.LastInsertId()
	return InsertBookAuthors(db, bookID, book.Authors)
}

func (BookController) Update(db *sql.DB, book Book) error {
	stmt, err := db.Prepare("UPDATE books SET title=?, year=?, isbn=? WHERE id=?")
	stmt.Exec(book.Title, book.Year, book.ISBN, book.ID)

	// remove all authors for this paper
	stmt, err = db.Prepare("DELETE FROM book_authors WHERE book=?")
	_, err = stmt.Exec(book.ID)

	if err != nil {
		fmt.Println(err)
	}

	return InsertPaperAuthors(db, book.ID, book.Authors)
}

func (BookController) Delete(db *sql.DB, id int64) error {
	if id <= 0 {
		return errors.New("Invalid ID")
	}

	_, err := db.Exec("DELETE FROM books WHERE id=?", id)

	return err
}

func (BookController) QueryByID(db *sql.DB, id int64) (Book, error) {
	if id <= 0 {
		return Book{}, errors.New("Invalid ID")
	}

	row := db.QueryRow("SELECT * FROM books WHERE id=?", id)

	return ScanBook(row, true)
}

func (BookController) QueryAll(db *sql.DB) ([]Book, error) {
	rows, err := db.Query("SELECT * FROM books")

	if err != nil {
		return []Book{}, err
	}

	return ScanBookRows(rows, true)
}

func (BookController) Search(db *sql.DB, title string) ([]Book, error) {
	rows, err := db.Query("SELECT * FROM books WHERE title LIKE ?", "%"+title+"%")

	if err != nil {
		return []Book{}, err
	}

	return ScanBookRows(rows, true)
}

func queryBooksByAuthor(db *sql.DB, authorID int64) ([]Book, error) {
	rows, err := db.Query(`
    SELECT books.*
    FROM books
    JOIN book_authors ON books.id = book_authors.book
    WHERE book_authors.author = ?
    `, authorID)

	if err != nil {
		return []Book{}, err
	}

	return ScanBookRows(rows, false)
}
