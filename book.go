package main

import "database/sql"

type Book struct {
	ID      int
	Title   string
	Authors []Author
}

func (b Book) String() string {
	return b.Title
}

func queryBooks(db *sql.DB) ([]Book, error) {
	var books []Book
	return books, nil
}
