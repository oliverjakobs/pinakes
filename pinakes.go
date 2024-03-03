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

type Scanable interface {
	Scan(dest ...any) error
}

func atoi(str string, fallback int) int {
	value, err := strconv.Atoi(str)

	if err != nil {
		return fallback
	}
	return value
}

var baseTmpl = template.New("base").Funcs(template.FuncMap{
	"joinNames": JoinAuthorNames,
	"lower":     strings.ToLower,
})

func renderTemplate(w http.ResponseWriter, filename string, data any) error {
	tmpl := template.Must(baseTmpl.Clone())
	template.Must(tmpl.ParseFiles(filename))

	return tmpl.ExecuteTemplate(w, "base", data)
}

// TODO rename PinakesTable
type ListView struct {
	Name     string
	Fields   []string
	AllowAdd bool
}

func renderListViewTemplate(w http.ResponseWriter, filename string, entries any, view ListView) error {
	tmpl := template.Must(baseTmpl.Clone())
	template.Must(tmpl.ParseFiles("./templates/_listview.html", filename))

	return tmpl.ExecuteTemplate(w, "base", struct {
		Name     string
		Fields   []string
		AllowAdd bool
		Entries  any
	}{
		Name:     view.Name,
		Fields:   view.Fields,
		AllowAdd: view.AllowAdd,
		Entries:  entries,
	})
}

func renderListEntriesTemplate(w http.ResponseWriter, filename string, data any) error {
	tmpl := template.Must(baseTmpl.Clone())
	template.Must(tmpl.ParseFiles(filename))

	return tmpl.ExecuteTemplate(w, "entries", data)
}

var db *sql.DB

// ==============================================================================
// FORM
// ==============================================================================
func handlePaperForm(w http.ResponseWriter, r *http.Request) {
	if r.Method == http.MethodPost {
		paper := Paper{
			Title: r.FormValue("title"),
			Year:  atoi(r.FormValue("year"), 0),
			DOI:   r.FormValue("doi"),
		}

		authors := r.FormValue("authors")

		if insertPaper(db, paper, authors) != nil {
			http.Error(w, "Failed to insert paper", http.StatusNotFound)
			return
		}

		http.Redirect(w, r, "/papers/", http.StatusSeeOther)
		return // ?
	}

	renderTemplate(w, "./templates/paper_form.html", nil)
}

func handlePaperFormEdit(w http.ResponseWriter, r *http.Request) {
	var c PaperController
	paper, err := c.QueryByID(db, atoi(chi.URLParam(r, "id"), 0))
	if err != nil {
		http.Error(w, err.Error(), http.StatusInternalServerError)
		return
	}

	if r.Method == http.MethodPost {
		paper.Title = r.FormValue("title")
		paper.Year = atoi(r.FormValue("year"), 0)
		paper.DOI = r.FormValue("doi")

		authors := r.FormValue("authors")

		if updatePaper(db, paper, authors) != nil {
			http.Error(w, "Failed to update paper", http.StatusNotFound)
			return
		}

		http.Redirect(w, r, "/papers/", http.StatusSeeOther)
		return // ?
	}

	renderTemplate(w, "./templates/paper_form.html", paper)
}

// ==============================================================================
// CONTROLLER
// ==============================================================================
type PinakesController[T any] interface {
	Delete(db *sql.DB, id int) error
	QueryByID(db *sql.DB, id int) (T, error)
	QueryAll(db *sql.DB) ([]T, error)
	Search(db *sql.DB, search string) ([]T, error)
}

func QueryAll[T any](c PinakesController[T], w http.ResponseWriter, r *http.Request, tmpl string, list ListView) {
	entries, err := c.QueryAll(db)

	if err != nil {
		http.Error(w, "Query failed: "+err.Error(), http.StatusNotFound)
		return
	}

	err = renderListViewTemplate(w, tmpl, entries, list)
	if err != nil {
		fmt.Println(err)
	}
}

func QueryByID[T any](c PinakesController[T], w http.ResponseWriter, r *http.Request, tmpl string) {
	id := atoi(chi.URLParam(r, "id"), 0)
	entry, err := c.QueryByID(db, id)

	if err != nil {
		http.Error(w, "Query failed: "+err.Error(), http.StatusNotFound)
		return
	}

	renderTemplate(w, tmpl, entry)
}

func Delete[T any](c PinakesController[T], w http.ResponseWriter, r *http.Request, redirect string) {
	id := atoi(chi.URLParam(r, "id"), 0)
	err := c.Delete(db, id)

	if err != nil {
		http.Error(w, "Deletion failed: "+err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Add("HX-Redirect", redirect)
	w.WriteHeader(http.StatusNoContent)
}

func Search[T any](c PinakesController[T], w http.ResponseWriter, r *http.Request, tmpl string) {
	name := r.FormValue("search")
	entries, err := c.Search(db, name)

	if err != nil {
		http.Error(w, "Search failed: "+err.Error(), http.StatusNotFound)
		return
	}

	renderListEntriesTemplate(w, tmpl, entries)
}

// ==============================================================================
// SETUP
// ==============================================================================
const port = 8000

func fillDatabase(db *sql.DB) {
	// create tables
	_, err := db.Exec(`CREATE TABLE IF NOT EXISTS papers (
		id    INTEGER PRIMARY KEY  NOT NULL,
		title TEXT                 NOT NULL,
		year  INTEGER,
		doi   TEXT
	)`)

	if err != nil {
		fmt.Print("Failed to create table papers")
	}
	_, err = db.Exec(`CREATE TABLE IF NOT EXISTS books (
		id    INTEGER PRIMARY KEY  NOT NULL,
		title TEXT                 NOT NULL,
		year  INTEGER,
		isbn  TEXT

	)`)

	if err != nil {
		fmt.Print("Failed to create table books")
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

	_, err = db.Exec(`CREATE TABLE IF NOT EXISTS book_authors (
		id     INTEGER PRIMARY KEY,
		book   INTEGER,
		author INTEGER,
		FOREIGN KEY (book) REFERENCES books(id) ON DELETE CASCADE,
		FOREIGN KEY (author) REFERENCES authors(id) ON DELETE CASCADE
	)`)

	if err != nil {
		fmt.Print("Failed to create table paper_authors")
	}

	// clear previous entries
	db.Exec("DELETE FROM papers")
	db.Exec("DELETE FROM books")
	db.Exec("DELETE FROM authors")
	db.Exec("DELETE FROM paper_authors")
	db.Exec("DELETE FROM book_authors")

	// insert test entries
	stmt, _ := db.Prepare("INSERT INTO papers (title, year, doi) VALUES (?, ?, ?)")

	stmt.Exec("Information Management: A Proposal", 1990, "")
	stmt.Exec("The Development of the C Language", 1996, "10.1145/234286.1057834")
	stmt.Exec("The UNIX Time-Sharing System", 1974, "10.1145/361011.361061")

	stmt, _ = db.Prepare("INSERT INTO books (title, year, isbn) VALUES (?, ?, ?)")

	stmt.Exec("The C Programming Language", 1978, "9780131101630")

	stmt, _ = db.Prepare("INSERT INTO authors (name) VALUES (?)")

	stmt.Exec("Tim Berners-Lee")
	stmt.Exec("Dennis M. Ritchie")
	stmt.Exec("Ken Thompson")
	stmt.Exec("Brian Kernighan")

	stmt, _ = db.Prepare("INSERT INTO paper_authors (paper, author) VALUES (?, ?)")

	stmt.Exec(1, 1)
	stmt.Exec(2, 2)
	stmt.Exec(3, 2)
	stmt.Exec(3, 3)

	stmt, _ = db.Prepare("INSERT INTO book_authors (book, author) VALUES (?, ?)")

	stmt.Exec(1, 2)
	stmt.Exec(1, 4)
}

func main() {
	r := chi.NewRouter()
	r.Use(middleware.Logger)

	fs := http.FileServer(http.Dir("./assets/"))
	r.Handle("/assets/*", http.StripPrefix("/assets/", fs))

	r.Get("/", func(w http.ResponseWriter, r *http.Request) {
		renderTemplate(w, "./templates/index.html", nil)
	})

	r.Route("/papers", func(r chi.Router) {
		var c PaperController
		r.Get("/", func(w http.ResponseWriter, r *http.Request) {
			QueryAll(c, w, r, "./templates/paper_list.html", ListView{
				Name:     "Papers",
				Fields:   []string{"Title", "Author(s)", "Year", "DOI"},
				AllowAdd: true,
			})
		})
		r.Get("/{id}", func(w http.ResponseWriter, r *http.Request) {
			QueryByID(c, w, r, "./templates/paper.html")
		})
		r.Delete("/{id}", func(w http.ResponseWriter, r *http.Request) {
			Delete(c, w, r, "/papers")
		})
		r.Get("/search", func(w http.ResponseWriter, r *http.Request) {
			Search(c, w, r, "./templates/paper_list.html")
		})
		r.Get("/form", handlePaperForm)
		r.Post("/form", handlePaperForm)
		r.Get("/form/{id}", handlePaperFormEdit)
		r.Post("/form/{id}", handlePaperFormEdit)
	})

	r.Route("/books", func(r chi.Router) {
		var c BookController
		r.Get("/", func(w http.ResponseWriter, r *http.Request) {
			QueryAll(c, w, r, "./templates/book_list.html", ListView{
				Name:     "Books",
				Fields:   []string{"Title", "Author(s)", "Year", "ISBN"},
				AllowAdd: true,
			})
		})
		r.Get("/{id}", func(w http.ResponseWriter, r *http.Request) {
			QueryByID(c, w, r, "./templates/book.html")
		})
		r.Delete("/{id}", func(w http.ResponseWriter, r *http.Request) {
			Delete(c, w, r, "/books")
		})
		r.Get("/search", func(w http.ResponseWriter, r *http.Request) {
			Search(c, w, r, "./templates/book_list.html")
		})
		/*
			r.Get("/form", handlePaperForm)
			r.Post("/form", handlePaperForm)
			r.Get("/form/{id}", handlePaperFormEdit)
			r.Post("/form/{id}", handlePaperFormEdit)
		*/
	})

	r.Route("/authors", func(r chi.Router) {
		var c AuthorController
		r.Get("/", func(w http.ResponseWriter, r *http.Request) {
			QueryAll(c, w, r, "./templates/author_list.html", ListView{
				Name:     "Authors",
				Fields:   []string{"Name", "Papers", "Books"},
				AllowAdd: false,
			})
		})
		r.Get("/{id}", func(w http.ResponseWriter, r *http.Request) {
			QueryByID(c, w, r, "./templates/author.html")
		})
		r.Delete("/{id}", func(w http.ResponseWriter, r *http.Request) {
			Delete(c, w, r, "/authors")
		})
		r.Get("/search", func(w http.ResponseWriter, r *http.Request) {
			Search(c, w, r, "./templates/author_list.html")
		})
	})

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
