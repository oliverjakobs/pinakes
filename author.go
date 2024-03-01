package main

import (
	"database/sql"
)

type Author struct {
	ID     int
	Name   string
	Papers []Paper
}

func (a Author) String() string {
	return a.Name
}

func joinAuthorNames(authors []Author) string {
	if len(authors) <= 0 {
		return ""
	}

	names := authors[0].Name
	for _, a := range authors[1:] {
		names += "; " + a.Name
	}

	return names
}

func queryAuthors(db *sql.DB) ([]Author, error) {
	rows, err := db.Query("SELECT * FROM authors")

	var authors []Author
	if err == nil {
		for rows.Next() {
			var author Author

			if rows.Scan(&author.ID, &author.Name) != nil {
				break
			}
			author.Papers, err = queryPapersByAuthor(db, author.ID)
			if err != nil {
				break
			}

			authors = append(authors, author)
		}
	}
	return authors, err
}

func queryAuthorByID(db *sql.DB, id int) (Author, error) {
	row := db.QueryRow("SELECT * FROM authors WHERE id=?", id)

	var author Author
	err := row.Scan(&author.ID, &author.Name)
	if err == nil {
		author.Papers, err = queryPapersByAuthor(db, id)
	}
	return author, err
}

func queryAuthorByName(db *sql.DB, name string) (Author, error) {
	row := db.QueryRow("SELECT * FROM authors WHERE name=?", name)

	var author Author
	err := row.Scan(&author.ID, &author.Name)
	if err == nil {
		author.Papers, err = queryPapersByAuthor(db, author.ID)
	}
	return author, err
}

func queryAuthorsByPaper(db *sql.DB, paperID int) ([]Author, error) {
	rows, err := db.Query(`
    SELECT authors.id, authors.name
    FROM authors
    JOIN paper_authors ON authors.id = paper_authors.author
    WHERE paper_authors.paper = ?
    `, paperID)

	var authors []Author
	if err == nil {
		for rows.Next() {
			var author Author

			err = rows.Scan(&author.ID, &author.Name)
			if err != nil {
				break
			}

			authors = append(authors, author)
		}
	}
	return authors, err
}
