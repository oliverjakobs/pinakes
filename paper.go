package main

import (
	"database/sql"
	"fmt"
	"strings"
)

type Paper struct {
	ID      int
	Title   string
	Year    int
	DOI     string
	Authors []Author
}

func (p Paper) String() string {
	return p.Title
}

func insertPaper(db *sql.DB, paper Paper, authors string) error {
	stmt, _ := db.Prepare("INSERT INTO papers (title, year, doi) VALUES (?, ?, ?)")

	result, _ := stmt.Exec(paper.Title, paper.Year, paper.DOI)

	paperID, _ := result.LastInsertId()

	stmtAuthor, _ := db.Prepare("INSERT INTO authors (name) VALUES (?)")
	stmtRel, _ := db.Prepare("INSERT INTO paper_authors (paper, author) VALUES (?, ?)")
	for _, n := range strings.Split(authors, ";") {
		name := strings.TrimSpace(n)
		author, err := queryAuthorByName(db, n)

		authorID := int64(author.ID)
		if err != nil {
			result, _ = stmtAuthor.Exec(name)
			authorID, _ = result.LastInsertId()
		}

		stmtRel.Exec(paperID, authorID)
	}

	return nil
}

func updatePaper(db *sql.DB, paper Paper, authors string) error {
	fmt.Println("Update paper")

	return nil
}

func deletePaper(db *sql.DB, id int) error {
	_, err := db.Exec("DELETE FROM papers WHERE id=?", id)

	return err
}

func queryPaperByID(db *sql.DB, id int) (Paper, error) {
	row := db.QueryRow("SELECT * FROM papers WHERE id=?", id)

	var paper Paper
	err := row.Scan(&paper.ID, &paper.Title, &paper.Year, &paper.DOI)
	if err == nil {
		paper.Authors, err = queryAuthorsByPaper(db, id)
	}
	return paper, err
}

func scanPaperRows(rows *sql.Rows) ([]Paper, error) {
	var papers []Paper
	var err error

	for rows.Next() {
		var paper Paper

		if rows.Scan(&paper.ID, &paper.Title, &paper.Year, &paper.DOI) != nil {
			break
		}
		paper.Authors, err = queryAuthorsByPaper(db, paper.ID)
		if err != nil {
			break
		}

		papers = append(papers, paper)
	}
	return papers, err
}

func queryPapers(db *sql.DB) ([]Paper, error) {
	rows, err := db.Query("SELECT * FROM papers")

	if err != nil {
		return []Paper{}, err
	}

	return scanPaperRows(rows)
}

func queryPapersLikeTitle(db *sql.DB, title string) ([]Paper, error) {
	rows, err := db.Query("SELECT * FROM papers WHERE title LIKE ?", "%"+title+"%")

	if err != nil {
		return []Paper{}, err
	}

	return scanPaperRows(rows)
}

func queryPapersByAuthor(db *sql.DB, authorID int) ([]Paper, error) {
	rows, err := db.Query(`
    SELECT papers.*
    FROM papers
    JOIN paper_authors ON papers.id = paper_authors.paper
    WHERE paper_authors.author = ?
    `, authorID)

	if err != nil {
		return []Paper{}, err
	}

	return scanPaperRows(rows)
}
