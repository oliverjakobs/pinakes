package main

import (
	"encoding/csv"
	"fmt"
	"html/template"
	"log"
	"net/http"
	"os"
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

func atoi(str string, fallback int64) int64 {
	value, err := strconv.Atoi(str)

	if err != nil {
		return fallback
	}
	return int64(value)
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

type PinakesTable struct {
	Name     string
	Fields   []string
	AllowAdd bool
	Entries  any
}

func renderTableTemplate(w http.ResponseWriter, filename string, table PinakesTable) error {
	tmpl := template.Must(baseTmpl.Clone())
	template.Must(tmpl.ParseFiles("./templates/_table.html", filename))

	return tmpl.ExecuteTemplate(w, "base", table)
}

func renderListEntriesTemplate(w http.ResponseWriter, filename string, data any) error {
	tmpl := template.Must(baseTmpl.Clone())
	template.Must(tmpl.ParseFiles(filename))

	return tmpl.ExecuteTemplate(w, "entries", data)
}

var db *sql.DB

// ==============================================================================
// CONTROLLER
// ==============================================================================
type PinakesController[T any] interface {
	Delete(db *sql.DB, id int64) error
	QueryByID(db *sql.DB, id int64) (T, error)
	QueryAll(db *sql.DB) ([]T, error)
	Search(db *sql.DB, search string) ([]T, error)
}

func HandleTable[T any](c PinakesController[T], w http.ResponseWriter, r *http.Request, tmpl string, table PinakesTable) {
	var err error
	table.Entries, err = c.QueryAll(db)

	if err != nil {
		http.Error(w, "Query failed: "+err.Error(), http.StatusNotFound)
		return
	}

	err = renderTableTemplate(w, tmpl, table)
	if err != nil {
		fmt.Println(err)
	}
}

func HandleSingle[T any](c PinakesController[T], w http.ResponseWriter, r *http.Request, tmpl string) {
	id := atoi(chi.URLParam(r, "id"), 0)
	entry, err := c.QueryByID(db, id)

	if err != nil {
		http.Error(w, "Query failed: "+err.Error(), http.StatusNotFound)
		return
	}

	renderTemplate(w, tmpl, entry)
}

func HandleDelete[T any](c PinakesController[T], w http.ResponseWriter, r *http.Request, redirect string) {
	id := atoi(chi.URLParam(r, "id"), 0)
	err := c.Delete(db, id)

	if err != nil {
		http.Error(w, "Deletion failed: "+err.Error(), http.StatusInternalServerError)
		return
	}

	w.Header().Add("HX-Redirect", redirect)
	w.WriteHeader(http.StatusNoContent)
}

func HandleSearch[T any](c PinakesController[T], w http.ResponseWriter, r *http.Request, tmpl string) {
	name := r.FormValue("search")
	entries, err := c.Search(db, name)

	if err != nil {
		http.Error(w, "Search failed: "+err.Error(), http.StatusNotFound)
		return
	}

	renderListEntriesTemplate(w, tmpl, entries)
}

// ==============================================================================
// FORM CONTROLLER
// ==============================================================================
type PinakesFormController[T any] interface {
	PinakesController[T]

	Parse(r *http.Request, id int64) T
	Insert(db *sql.DB, entry T) error
	Update(db *sql.DB, entry T) error
}

func HandleFormSubmit[T any](c PinakesFormController[T], w http.ResponseWriter, r *http.Request, redirect string) {
	entry := c.Parse(r, 0)

	err := c.Insert(db, entry)
	if err != nil {
		http.Error(w, "Insert failed: "+err.Error(), http.StatusNotFound)
		return
	}

	http.Redirect(w, r, redirect, http.StatusSeeOther)
}

func HandleEditSubmit[T any](c PinakesFormController[T], w http.ResponseWriter, r *http.Request, redirect string) {
	id := atoi(chi.URLParam(r, "id"), 0)
	entry := c.Parse(r, id)

	err := c.Update(db, entry)
	if err != nil {
		http.Error(w, "Update failed: "+err.Error(), http.StatusNotFound)
		return
	}

	http.Redirect(w, r, redirect, http.StatusSeeOther)
}

// ==============================================================================
// SETUP
// ==============================================================================
func ReadCSV(filename string) [][]string {
	f, err := os.Open(filename)
	if err != nil {
		log.Fatal("Unable to read input file "+filename, err)
	}
	defer f.Close()

	csvReader := csv.NewReader(f)
	records, err := csvReader.ReadAll()
	if err != nil {
		log.Fatal("Unable to parse file as CSV for "+filename, err)
	}

	return records
}

func ImportCSV(filename string) {
	records := ReadCSV(filename)

	var c PaperController
	for _, row := range records[1:] {
		c.Insert(db, Paper{
			ID:      0,
			Title:   row[0],
			Authors: ParseAuthors(row[1]),
			Year:    int(atoi(row[2], 0)),
			DOI:     row[3],
		})
	}
}

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
	var pc PaperController
	pc.Insert(db, Paper{
		ID:      0,
		Title:   "Information Management: A Proposal",
		Authors: ParseAuthors("Tim Berners-Lee"),
		Year:    1990,
		DOI:     "",
	})

	pc.Insert(db, Paper{
		ID:      0,
		Title:   "The Development of the C Language",
		Authors: ParseAuthors("Dennis M. Ritchie"),
		Year:    1996,
		DOI:     "10.1145/234286.1057834",
	})

	pc.Insert(db, Paper{
		ID:      0,
		Title:   "The UNIX Time-Sharing System",
		Authors: ParseAuthors("Dennis M. Ritchie;Ken Thompson"),
		Year:    1974,
		DOI:     "10.1145/361011.361061",
	})

	var bc BookController
	bc.Insert(db, Book{
		ID:      0,
		Title:   "The C Programming Language",
		Authors: ParseAuthors("Dennis M. Ritchie;Brian Kernighan"),
		Year:    1978,
		ISBN:    "9780131101630",
	})
}

func main() {
	fmt.Println("Starting Pinakes...")

	r := chi.NewRouter()
	r.Use(middleware.Logger)

	fs := http.FileServer(http.Dir("./assets/"))
	r.Handle("/assets/*", http.StripPrefix("/assets/", fs))

	r.Get("/", func(w http.ResponseWriter, r *http.Request) {
		renderTemplate(w, "./templates/index.html", nil)
	})

	// paper routes
	r.Route("/papers", func(r chi.Router) {
		var c PaperController
		r.Get("/", func(w http.ResponseWriter, r *http.Request) {
			HandleTable(c, w, r, "./templates/paper_list.html", PinakesTable{
				Name:     "Papers",
				Fields:   []string{"Title", "Author(s)", "Year", "DOI"},
				AllowAdd: true,
			})
		})
		r.Get("/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleSingle(c, w, r, "./templates/paper.html")
		})
		r.Delete("/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleDelete(c, w, r, "/papers")
		})
		r.Get("/search", func(w http.ResponseWriter, r *http.Request) {
			HandleSearch(c, w, r, "./templates/paper_list.html")
		})
		// Forms
		r.Get("/form", func(w http.ResponseWriter, r *http.Request) {
			renderTemplate(w, "./templates/paper_form.html", nil)
		})
		r.Get("/form/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleSingle(c, w, r, "./templates/paper_form.html")
		})
		r.Post("/form", func(w http.ResponseWriter, r *http.Request) {
			HandleFormSubmit(c, w, r, "/papers")
		})
		r.Post("/form/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleEditSubmit(c, w, r, "/papers")
		})
	})

	// book routes
	r.Route("/books", func(r chi.Router) {
		var c BookController
		r.Get("/", func(w http.ResponseWriter, r *http.Request) {
			HandleTable(c, w, r, "./templates/book_list.html", PinakesTable{
				Name:     "Books",
				Fields:   []string{"Title", "Author(s)", "Year", "ISBN"},
				AllowAdd: true,
			})
		})
		r.Get("/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleSingle(c, w, r, "./templates/book.html")
		})
		r.Delete("/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleDelete(c, w, r, "/books")
		})
		r.Get("/search", func(w http.ResponseWriter, r *http.Request) {
			HandleSearch(c, w, r, "./templates/book_list.html")
		})
		// Forms
		r.Get("/form", func(w http.ResponseWriter, r *http.Request) {
			renderTemplate(w, "./templates/book_form.html", nil)
		})
		r.Get("/form/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleSingle(c, w, r, "./templates/book_form.html")
		})
		r.Post("/form", func(w http.ResponseWriter, r *http.Request) {
			HandleFormSubmit(c, w, r, "/books")
		})
		r.Post("/form/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleEditSubmit(c, w, r, "/books")
		})
	})

	// author routes
	r.Route("/authors", func(r chi.Router) {
		var c AuthorController
		r.Get("/", func(w http.ResponseWriter, r *http.Request) {
			HandleTable(c, w, r, "./templates/author_list.html", PinakesTable{
				Name:     "Authors",
				Fields:   []string{"Name", "Papers", "Books"},
				AllowAdd: false,
			})
		})
		r.Get("/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleSingle(c, w, r, "./templates/author.html")
		})
		r.Delete("/{id}", func(w http.ResponseWriter, r *http.Request) {
			HandleDelete(c, w, r, "/authors")
		})
		r.Get("/search", func(w http.ResponseWriter, r *http.Request) {
			HandleSearch(c, w, r, "./templates/author_list.html")
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

	//ImportCSV("./papers.csv")

	fmt.Println("Done.")
	fmt.Printf("Listening on port %d\n", port)
	log.Fatal(http.ListenAndServe(fmt.Sprintf(":%d", port), r))
}
