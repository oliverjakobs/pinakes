package main

import (
	"fmt"
	"html/template"
	"log"
	"net/http"
	"strconv"

	"database/sql"

	_ "github.com/mattn/go-sqlite3"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
)

var baseTmpl = template.New("base").Funcs(template.FuncMap{
	"joinNames": joinAuthorNames,
})

var db *sql.DB

func renderTemplate(w http.ResponseWriter, name string, data any) error {
	tmpl := template.Must(baseTmpl.Clone())
	template.Must(tmpl.ParseFiles(name))

	return tmpl.ExecuteTemplate(w, "base", data)
}

func handlePaperList(w http.ResponseWriter, r *http.Request) {
	papers, err := queryPapers(db)

	if err != nil {
		http.Error(w, "Query failed", http.StatusNotFound)
		return
	}

	err = renderTemplate(w, "./templates/paper_list.html", papers)

	if err != nil {
		fmt.Printf("Error: %s\n", err)
		http.Error(w, "Failed to render template", http.StatusNotFound)
		return
	}
}

func handlePaper(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.Atoi(chi.URLParam(r, "id"))

	if err != nil {
		http.Error(w, "Invalid ID", http.StatusNotFound)
		return
	}

	paper, err := queryPaperByID(db, id)
	if err != nil {
		http.Error(w, "Failed to query for paper", http.StatusInternalServerError)
		return
	}

	renderTemplate(w, "./templates/paper.html", paper)
}

func handlePaperDelete(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.Atoi(chi.URLParam(r, "id"))

	if err != nil {
		http.Error(w, "Invalid ID", http.StatusNotFound)
		return
	}

	err = deletePaper(db, id)
	if err != nil {
		http.Error(w, "Failed to delete paper", http.StatusInternalServerError)
		return
	}

	w.Header().Add("HX-Redirect", "/papers/")
	w.WriteHeader(http.StatusNoContent)
}

func handlePaperForm(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.Atoi(chi.URLParam(r, "id"))

	var paper Paper

	if err == nil {
		paper, _ = queryPaperByID(db, id)
	}

	renderTemplate(w, "./templates/paper_form.html", paper)
}

func handlePaperFormSubmit(w http.ResponseWriter, r *http.Request) {
	title := r.FormValue("title")
	year, _ := strconv.Atoi(r.FormValue("year"))
	doi := r.FormValue("doi")
	authors := r.FormValue("authors")

	err := insertPaper(db, title, year, doi, authors)
	if err != nil {
		http.Error(w, "Failed to insert paper", http.StatusNotFound)
		return
	}

	http.Redirect(w, r, "/papers/", http.StatusSeeOther)
}

func handleAuthorList(w http.ResponseWriter, r *http.Request) {
	authors, err := queryAuthors(db)

	if err != nil {
		http.Error(w, "Query failed", http.StatusNotFound)
		return
	}

	renderTemplate(w, "./templates/author_list.html", authors)
}

func handleAuthor(w http.ResponseWriter, r *http.Request) {
	id, err := strconv.Atoi(chi.URLParam(r, "id"))

	if err != nil {
		http.Error(w, "Invalid ID", http.StatusNotFound)
		return
	}

	author, err := queryAuthorByID(db, id)
	if err != nil {
		http.Error(w, "Failed to query for author", http.StatusNotFound)
		return
	}

	renderTemplate(w, "./templates/author.html", author)
}

func handleBookList(w http.ResponseWriter, r *http.Request) {
	books, err := queryBooks(db)

	if err != nil {
		http.Error(w, "Query failed", http.StatusNotFound)
		return
	}

	renderTemplate(w, "./templates/book_list.html", books)
}

func handler(w http.ResponseWriter, r *http.Request) {
	renderTemplate(w, "./templates/index.html", nil)
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
		id     INTEGER PRIMARY KEY,
		paper  INTEGER,
		author INTEGER,
		FOREIGN KEY (paper) REFERENCES papers(id) ON DELETE CASCADE,
		FOREIGN KEY (author) REFERENCES authors(id) ON DELETE CASCADE
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

	r.Route("/papers", func(r chi.Router) {
		r.Get("/", handlePaperList)
		r.Get("/{id}", handlePaper)
		r.Delete("/{id}", handlePaperDelete)
		r.Get("/form", handlePaperForm)
		r.Get("/form/{id}", handlePaperForm)
		r.Post("/form", handlePaperFormSubmit)
	})

	r.Get("/books/", handleBookList)

	r.Get("/authors/", handleAuthorList)
	r.Get("/authors/{id}", handleAuthor)

	// init base template
	_, err := baseTmpl.ParseFiles("./templates/_base.html")
	if err != nil {
		fmt.Print("Failed to parse templates: %s", err)
	}

	db, err = sql.Open("sqlite3", "./pinakes.db")
	if err != nil {
		panic(err)
	}

	db.Exec("PRAGMA foreign_keys=ON")
	fillDatabase(db)

	fmt.Printf("Listening on port %d\n", port)
	log.Fatal(http.ListenAndServe(fmt.Sprintf(":%d", port), r))
}
