
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

function newEntry(name) {
    let element = htmx.find('#autocomplete-' + name);
    element.appendChild(htmx.find('#new_entry').content.cloneNode(true));
    updateIds(name, element);
}

function deleteEntry(name, index) {
    htmx.remove('#' + name + index);
    updateIds(name, htmx.find('#autocomplete-' + name));
}
