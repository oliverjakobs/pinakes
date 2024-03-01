package main

import (
	"database/sql"
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

func insertPaper(db *sql.DB, title string, year int, doi string, authors string) error {
	stmt, _ := db.Prepare("INSERT INTO papers (title, year, doi) VALUES (?, ?, ?)")

	result, _ := stmt.Exec(title, year, doi)

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

func deletePaper(db *sql.DB, id int) error {
	_, err := db.Exec("DELETE FROM papers WHERE id=?", id)
	//_, err = db.Exec("DELETE FROM paper_authors WHERE paper=?", id)

	return err
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
			paper.Authors, err = queryAuthorsByPaper(db, paper.ID)
			if err != nil {
				break
			}

			papers = append(papers, paper)
		}
	}
	return papers, err
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

func queryPapersByAuthor(db *sql.DB, authorID int) ([]Paper, error) {
	rows, err := db.Query(`
    SELECT papers.id, papers.title, papers.year, papers.doi
    FROM papers
    JOIN paper_authors ON papers.id = paper_authors.paper
    WHERE paper_authors.author = ?
    `, authorID)

	var papers []Paper
	if err == nil {
		for rows.Next() {

			var paper Paper
			err := rows.Scan(&paper.ID, &paper.Title, &paper.Year, &paper.DOI)
			if err != nil {
				break
			}

			papers = append(papers, paper)
		}
	}
	return papers, err
}
