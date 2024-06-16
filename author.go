package main

import (
	"database/sql"
	"errors"
	"strings"
)

type Author struct {
	ID     int64
	Name   string
	Papers []Paper
	Books  []Book
}

func (a Author) String() string {
	return a.Name
}

func ParseAuthors(authors string) []Author {
	a := []Author{}
	for _, name := range strings.Split(authors, ";") {
		name := strings.TrimSpace(name)
		a = append(a, Author{Name: name})
	}
	return a
}

func JoinAuthorNames(authors []Author) string {
	if len(authors) <= 0 {
		return ""
	}

	names := authors[0].Name
	for _, a := range authors[1:] {
		names += "; " + a.Name
	}

	return names
}

func ScanAuthor(row Scanable, queryWork bool) (Author, error) {
	var author Author
	err := row.Scan(&author.ID, &author.Name)

	if queryWork && err == nil {
		author.Papers, err = queryPapersByAuthor(db, author.ID)
		author.Books, err = queryBooksByAuthor(db, author.ID)
	}

	return author, err
}

func ScanAuthorRows(rows *sql.Rows, queryWork bool) ([]Author, error) {
	var authors []Author
	var err error

	for rows.Next() {
		author, err := ScanAuthor(rows, queryWork)

		if err != nil {
			break
		}

		authors = append(authors, author)
	}
	return authors, err
}

type AuthorController struct{}

func (AuthorController) Delete(db *sql.DB, id int64) error {
	if id <= 0 {
		return errors.New("Invalid ID")
	}

	_, err := db.Exec("DELETE FROM authors WHERE id=?", id)

	return err
}

func (AuthorController) QueryByID(db *sql.DB, id int64) (Author, error) {
	var author Author

	if id <= 0 {
		return author, errors.New("Invalid ID")
	}

	row := db.QueryRow("SELECT * FROM authors WHERE id=?", id)

	return ScanAuthor(row, true)
}

func (AuthorController) QueryAll(db *sql.DB) ([]Author, error) {
	rows, err := db.Query("SELECT * FROM authors")

	if err != nil {
		return []Author{}, err
	}

	return ScanAuthorRows(rows, true)
}

func (AuthorController) Search(db *sql.DB, name string) ([]Author, error) {
	rows, err := db.Query("SELECT * FROM authors WHERE name LIKE ?", "%"+name+"%")

	if err != nil {
		return []Author{}, err
	}

	return ScanAuthorRows(rows, true)
}

// TODO change to findIDForName
func queryAuthorByName(db *sql.DB, name string) (Author, error) {
	row := db.QueryRow("SELECT * FROM authors WHERE name=?", name)

	return ScanAuthor(row, false)
}

func queryAuthorsByPaper(db *sql.DB, paperID int64) ([]Author, error) {
	rows, err := db.Query(`
    SELECT authors.id, authors.name
    FROM authors
    JOIN paper_authors ON authors.id = paper_authors.author
    WHERE paper_authors.paper = ?
    `, paperID)

	if err != nil {
		return []Author{}, err
	}

	return ScanAuthorRows(rows, false)
}

func queryAuthorsByBook(db *sql.DB, bookID int64) ([]Author, error) {
	rows, err := db.Query(`
    SELECT authors.id, authors.name
    FROM authors
    JOIN book_authors ON authors.id = book_authors.author
    WHERE book_authors.book = ?
    `, bookID)

	if err != nil {
		return []Author{}, err
	}

	return ScanAuthorRows(rows, false)
}
