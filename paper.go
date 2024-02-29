package main

import (
	"database/sql"
	"fmt"
	"strings"
)

type Paper struct {
	ID      int
	Title   string
	Authors []string
	Year    int
	DOI     string
}

func (p Paper) print() {
	fmt.Printf("%d: %s (%d) - %s\n", p.ID, p.Title, p.Year, strings.Join(p.Authors, "; "))
}

func queryAuthors(db *sql.DB, paperID int) ([]string, error) {
	rows, err := db.Query(`
    SELECT authors.name
    FROM authors
    JOIN paper_authors ON authors.id = paper_authors.author
    WHERE paper_authors.paper = ?
    `, paperID)

	var names []string
	if err == nil {
		for rows.Next() {
			var name string

			err = rows.Scan(&name)
			if err != nil {
				break
			}

			names = append(names, name)
		}
	}
	return names, err
}

func queryPapers(db *sql.DB) ([]Paper, error) {
	rows, err := db.Query("SELECT * FROM papers")

	var papers []Paper
	if err == nil {
		for rows.Next() {
			var paper Paper

			if rows.Scan(&paper.ID, &paper.Title, &paper.Year, &paper.DOI) != nil {
				break
			}
			paper.Authors, err = queryAuthors(db, paper.ID)
			if err != nil {
				break
			}

			papers = append(papers, paper)
		}
	}
	return papers, err
}

func queryPaper(db *sql.DB, paperID int) (Paper, error) {
	row := db.QueryRow("SELECT * FROM papers WHERE id=?", paperID)

	var paper Paper
	err := row.Scan(&paper.ID, &paper.Title, &paper.Year, &paper.DOI)
	if err == nil {
		paper.Authors, err = queryAuthors(db, paperID)
	}
	return paper, err
}
