
const searchInput = document.querySelector("#product-search");
const productCards = document.querySelectorAll(".product-card");
const emptyMessage = document.querySelector("#empty-message");

if (searchInput) {
    searchInput.addEventListener("input", function () {
        const keyword = searchInput.value
            .trim()
            .toLocaleLowerCase("vi");

        let visibleCount = 0;

        productCards.forEach(function (card) {
            const searchText = (
                card.dataset.search || ""
            ).toLocaleLowerCase("vi");

            const matches = searchText.includes(keyword);

            card.hidden = !matches;

            if (matches) {
                visibleCount++;
            }
        });

        if (emptyMessage) {
            emptyMessage.hidden = visibleCount !== 0;
        }
    });
}
