
function updateIds(name, element) {
    Array.from(element.children).forEach((child, idx) => {
        child.id = name + idx;

        child.getElementsByTagName("input")[0].setAttribute(
            "name",
            child.id
        );
        child.getElementsByClassName("delete")[0].setAttribute(
            "onclick",
            `deleteEntry('${name}', ${idx})`
        );
    });
}

function newEntry(name, list_name) {
    let new_entry = document.createElement('li');
    new_entry.innerHTML = `<input type="text" list="${list_name}"><div class="delete">X</div>`;

    let element = htmx.find('#autocomplete-' + name);
    element.appendChild(new_entry);
    updateIds(name, element);
}

function deleteEntry(name, index) {
    htmx.remove('#' + name + index);
    updateIds(name, htmx.find('#autocomplete-' + name));
}
