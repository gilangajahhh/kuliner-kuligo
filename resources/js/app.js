import './bootstrap';

const search = document.querySelector('#menu-search');
const rows = [...document.querySelectorAll('.table-panel tbody tr')];
const tabs = [...document.querySelectorAll('.filter-tab')];
let category = 'all';

function filterMenu() {
    const query = search?.value.trim().toLocaleLowerCase('id') ?? '';
    rows.forEach((row) => {
        const text = row.textContent.toLocaleLowerCase('id');
        const categoryText = row.querySelector('.category-tag')?.textContent.toLocaleLowerCase('id') ?? '';
        const categoryMatches = category === 'all' || categoryText.includes(category);
        row.hidden = !text.includes(query) || !categoryMatches;
    });
}

search?.addEventListener('input', filterMenu);
tabs.forEach((tab, index) => tab.addEventListener('click', () => {
    category = index === 1 ? 'makanan' : index === 2 ? 'minuman' : 'all';
    tabs.forEach((item) => item.classList.toggle('chosen', item === tab));
    filterMenu();
}));
