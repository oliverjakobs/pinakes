package main

import (
	"database/sql"
	"errors"
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

func (BookController) Insert(db *sql.DB, book Book) error {
	return nil
}

func (BookController) Update(db *sql.DB, book Book) error {
	return nil
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
