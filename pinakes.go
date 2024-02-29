package main

import (
	"fmt"
	"html/template"
	"log"
	"net/http"
	"strconv"
	"strings"

	"database/sql"

	_ "github.com/mattn/go-sqlite3"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
)

var baseTmpl = template.New("base").Funcs(template.FuncMap{
	"join": strings.Join,
})

var db *sql.DB

func handlePapers(w http.ResponseWriter, r *http.Request) {
	papers, err := queryPapers(db)

	if err != nil {
		http.Error(w, "Query failed", http.StatusNotFound)
		return
	}

	tmpl := template.Must(baseTmpl.Clone())
	tmpl.ExecuteTemplate(w, "papers.html", papers)
}

func handlePaperDetail(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.Atoi(chi.URLParam(r, "id"))

	if err != nil {
		http.Error(w, "Invalid ID", http.StatusNotFound)
		return
	}

	paper, err := queryPaper(db, id)
	if err != nil {
		http.Error(w, "Failed to query for paper", http.StatusNotFound)
		return
	}

	tmpl := template.Must(baseTmpl.Clone())
	tmpl.ExecuteTemplate(w, "paper_detail.html", paper)
}

func handler(w http.ResponseWriter, r *http.Request) {
	tmpl := template.Must(baseTmpl.Clone())
	tmpl.ExecuteTemplate(w, "index.html", nil)
}

const port = 8000

func fillDatabase(db *sql.DB) {
	// create tables
	_, err := db.Exec(`CREATE TABLE IF NOT EXISTS papers (
		id    INTEGER PRIMARY KEY,
		title TEXT,
		year  INTEGER,
		doi   TEXT
	)`)

	if err != nil {
		fmt.Print("Failed to create table papers")
	}

	_, err = db.Exec(`CREATE TABLE IF NOT EXISTS authors (
		id   INTEGER PRIMARY KEY,
		name TEXT
	)`)

	if err != nil {
		fmt.Print("Failed to create table authors")
	}

	_, err = db.Exec(`CREATE TABLE IF NOT EXISTS paper_authors (
		paper  INTEGER REFERENCES papers,
		author INTEGER REFERENCES authors
	)`)

	if err != nil {
		fmt.Print("Failed to create table paper_authors")
	}

	// clear previous entries
	db.Exec("DELETE FROM papers")
	db.Exec("DELETE FROM authors")
	db.Exec("DELETE FROM paper_authors")

	// insert test entries
	stmt, _ := db.Prepare("INSERT INTO papers (title, year, doi) VALUES (?, ?, ?)")

	stmt.Exec("Information Management: A Proposal", 1990, "")
	stmt.Exec("The Development of the C Language", 1996, "10.1145/234286.1057834")
	stmt.Exec("The UNIX Time-Sharing System", 1974, "10.1145/361011.361061")

	stmt, _ = db.Prepare("INSERT INTO authors (name) VALUES (?)")

	stmt.Exec("Tim Berners-Lee")
	stmt.Exec("Dennis M. Ritchie")
	stmt.Exec("Ken Thompson")

	stmt, _ = db.Prepare("INSERT INTO paper_authors (paper, author) VALUES (?, ?)")

	stmt.Exec(1, 1)
	stmt.Exec(2, 2)
	stmt.Exec(3, 2)
	stmt.Exec(3, 3)
}

func main() {
	r := chi.NewRouter()
	r.Use(middleware.Logger)

	fs := http.FileServer(http.Dir("./assets/"))
	r.Handle("/assets/*", http.StripPrefix("/assets/", fs))

	r.Get("/", handler)
	r.Get("/papers/", handlePapers)
	r.Get("/papers/{id}", handlePaperDetail)

	// init base template
	_, err := baseTmpl.ParseGlob("./templates/*.html")
	if err != nil {
		fmt.Print("Failed to parse templates")
	}

	db, err = sql.Open("sqlite3", "./pinakes.db")
	if err != nil {
		panic(err)
	}

	fillDatabase(db)

	/*
		rows, _ := db.Query("SELECT * FROM papers")
		for rows.Next() {
			var p paper
			var authors string

			rows.Scan(&p.ID, &p.Title, &authors, &p.Year, &p.DOI)
			p.Authors = strings.Split(authors, "; ")

			p.printPaper()
		}
	*/

	fmt.Printf("Listening on port %d\n", port)
	log.Fatal(http.ListenAndServe(fmt.Sprintf(":%d", port), r))
}
