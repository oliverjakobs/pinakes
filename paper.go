package main

import (
	"database/sql"
	"errors"
	"fmt"
	"net/http"
	"strings"
)

type Paper struct {
	ID      int64
	Title   string
	Year    int
	DOI     string
	Authors []Author
}

func (p Paper) String() string {
	return p.Title
}

func ScanPaper(row Scanable, queryAuthor bool) (Paper, error) {
	var paper Paper
	err := row.Scan(&paper.ID, &paper.Title, &paper.Year, &paper.DOI)

	if queryAuthor && err == nil {
		paper.Authors, err = queryAuthorsByPaper(db, paper.ID)
	}

	return paper, err
}

func ScanPaperRows(rows *sql.Rows, queryAuthor bool) ([]Paper, error) {
	var papers []Paper
	var err error

	for rows.Next() {
		paper, err := ScanPaper(rows, queryAuthor)

		if err != nil {
			break
		}

		papers = append(papers, paper)
	}
	return papers, err
}

type PaperController struct{}

func (PaperController) Parse(r *http.Request, id int64) Paper {
	paper := Paper{
		ID:    id,
		Title: r.FormValue("title"),
		Year:  int(atoi(r.FormValue("year"), 0)),
		DOI:   r.FormValue("doi"),
	}

	authors := r.FormValue("authors")
	for _, name := range strings.Split(authors, ";") {
		name := strings.TrimSpace(name)
		paper.Authors = append(paper.Authors, Author{Name: name})
	}
	return paper
}

func InsertPaperAuthors(db *sql.DB, paperID int64, authors []Author) error {
	stmtAuthor, _ := db.Prepare("INSERT INTO authors (name) VALUES (?)")
	stmtRel, _ := db.Prepare("INSERT INTO paper_authors (paper, author) VALUES (?, ?)")

	for _, a := range authors {
		author, err := queryAuthorByName(db, a.Name)

		authorID := author.ID
		if err != nil {
			result, _ := stmtAuthor.Exec(a.Name)
			authorID, _ = result.LastInsertId()
		}

		stmtRel.Exec(paperID, authorID)
	}

	return nil
}

func (PaperController) Insert(db *sql.DB, paper Paper) error {
	stmt, _ := db.Prepare("INSERT INTO papers (title, year, doi) VALUES (?, ?, ?)")
	result, _ := stmt.Exec(paper.Title, paper.Year, paper.DOI)

	paperID, _ := result.LastInsertId()
	return InsertPaperAuthors(db, paperID, paper.Authors)
}

func (PaperController) Update(db *sql.DB, paper Paper) error {
	stmt, err := db.Prepare("UPDATE papers SET title=?, year=?, doi=? WHERE id=?")
	stmt.Exec(paper.Title, paper.Year, paper.DOI, paper.ID)

	// remove all authors for this paper
	stmt, err = db.Prepare("DELETE FROM paper_authors WHERE paper=?")
	_, err = stmt.Exec(paper.ID)

	if err != nil {
		fmt.Println(err)
	}

	return InsertPaperAuthors(db, paper.ID, paper.Authors)
}

func (PaperController) Delete(db *sql.DB, id int64) error {
	if id <= 0 {
		return errors.New("Invalid ID")
	}

	_, err := db.Exec("DELETE FROM papers WHERE id=?", id)

	return err
}

func (PaperController) QueryByID(db *sql.DB, id int64) (Paper, error) {
	if id <= 0 {
		return Paper{}, errors.New("Invalid ID")
	}

	row := db.QueryRow("SELECT * FROM papers WHERE id=?", id)

	return ScanPaper(row, true)
}

func (PaperController) QueryAll(db *sql.DB) ([]Paper, error) {
	rows, err := db.Query("SELECT * FROM papers")

	if err != nil {
		return []Paper{}, err
	}

	return ScanPaperRows(rows, true)
}

func (PaperController) Search(db *sql.DB, title string) ([]Paper, error) {
	rows, err := db.Query("SELECT * FROM papers WHERE title LIKE ?", "%"+title+"%")

	if err != nil {
		return []Paper{}, err
	}

	return ScanPaperRows(rows, true)
}

func queryPapersByAuthor(db *sql.DB, authorID int64) ([]Paper, error) {
	rows, err := db.Query(`
    SELECT papers.*
    FROM papers
    JOIN paper_authors ON papers.id = paper_authors.paper
    WHERE paper_authors.author = ?
    `, authorID)

	if err != nil {
		return []Paper{}, err
	}

	return ScanPaperRows(rows, false)
}
