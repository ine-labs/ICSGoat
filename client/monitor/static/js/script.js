document.addEventListener("DOMContentLoaded", function () {
    function updateData() {
        fetch('/data')
            .then(response => response.json())
            .then(data => {
                data.forEach(item => {
                    console.log(item);
                    const elementId = `${item.variable_name}-${item.process_name}`;
                    console.log(elementId);
                    const element = document.getElementById(elementId);
                    if (element) {
                        element.textContent = item.value;
                    }
                });
            })
            .catch(error => console.error('Error fetching data:', error));
    }

    // Update data every 2 seconds
    setInterval(updateData, 2000);

    // Initial call to populate data immediately on page load
    updateData();
});