document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    const searchButton = document.getElementById("searchButton");
    const tableBody = document.getElementById("smartlockTableBody");
    const sortButtonByPerc  = document.getElementById("sortButtonByPerc");
    const paginationControls = document.getElementById("paginationControls");

    let originalBatteryData = Array.isArray(batteryData) ? batteryData : [];
    let filteredData = [...originalBatteryData];
    let currentPage = 1;
    const batteriesPerPage = 25;
    let sortDirection = 'asc';

    // Function to filter the table rows based on the search input
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase(); // Get the input value and convert to lowercase for case-insensitive search
        const rows = tableBody.querySelectorAll("tr"); // Get all table rows

        // Loop through each row in the table
        rows.forEach((row) => {
            const smartlockName = row.querySelector("td").innerText.toLowerCase(); // Get the smartlock name from the first <td> in each row
            // Show or hide the row based on the search term
            if (smartlockName.includes(searchTerm)) {
                row.style.display = ""; // Show row if it matches the search
            } else {
                row.style.display = "none"; // Hide row if it doesn't match
            }
        });

        // re-sort after filtering
        sortData(filteredData);
        currentPage = 1;
        renderTable(filteredData, currentPage);
    }

    // Create a table row for a battery data
    function appendBatteryRow(battery) {
        const row = document.createElement("tr");

        // Device Name
        const nameCell = document.createElement("td");
        nameCell.textContent = battery.name || "";
        row.appendChild(nameCell);

        // Battery Status
        const statusCell = document.createElement("td");
        if (battery.state && battery.state.batteryCritical) {
            statusCell.innerHTML = '<span class="badge bg-danger">Critical</span>';
        } else {
            statusCell.innerHTML = '<span class="badge bg-success">Normal</span>';
        }
        row.appendChild(statusCell);

        // Battery Charge
        const chargeCell = document.createElement("td");
        chargeCell.textContent = (battery.state && battery.state.batteryCharge !== undefined)
            ? battery.state.batteryCharge + "%"
            : "Not available";
        row.appendChild(chargeCell);

        // Battery Type
        const typeCell = document.createElement("td");
        if (battery.advancedConfig && battery.advancedConfig.batteryType !== undefined) {
            const mapping = ["Alkali", "Accumulator", "Lithium"];
            typeCell.textContent = mapping[battery.advancedConfig.batteryType] || "Unknown";
        } else {
            typeCell.textContent = "Not available";
        }
        row.appendChild(typeCell);

        tableBody.appendChild(row);
    }

    /**
     * Renders pagination controls (Previous / Next + current page info)
     */
    function renderPaginationControls(totalBatteries, page) {
        paginationControls.innerHTML = "";

        const totalPages = Math.ceil(totalBatteries / batteriesPerPage);
        if (totalPages <= 1) return;

        // Show page info
        const pageInfo = document.createElement("span");
        pageInfo.classList.add("me-3");
        pageInfo.textContent = `Page ${page} of ${totalPages}`;

        paginationControls.appendChild(pageInfo);

        // Prev Button
        const prevButton = document.createElement("button");
        prevButton.innerText = "Previous";
        prevButton.disabled = page === 1;
        prevButton.classList.add("btn", "btn-primary", "me-2");
        prevButton.addEventListener("click", () => {
            if (currentPage > 1) {
                currentPage--;
                renderTable(filteredData, currentPage);
            }
        });

        // Next Button
        const nextButton = document.createElement("button");
        nextButton.innerText = "Next";
        nextButton.disabled = page === totalPages;
        nextButton.classList.add("btn", "btn-primary");
        nextButton.addEventListener("click", () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderTable(filteredData, currentPage);
            }
        });

        paginationControls.appendChild(prevButton);
        paginationControls.appendChild(nextButton);
    }

    /**
     * 
     */
    function renderTable(data, page) {
        tableBody.innerHTML = '';

        const startIndex = (page - 1) * batteriesPerPage;
        const endIndex = Math.min(startIndex + batteriesPerPage, data.length);
        const batteriesToDisplay = data.slice(startIndex, endIndex);

        if (batteriesToDisplay.length > 0) {
            batteriesToDisplay.forEach(battery => appendBatteryRow(battery));
        } else {
            tableBody.innerHTML = 
                '<tr><td colspan="5" class="text-center">No Data Found</td></tr>';
        }

        // Render pagination controls
        renderPaginationControls(data.length, page);
    }

    /**
     * Sorts data by battery percentage in ascending/descending
     */
    function sortData(data) {
        data.sort((a, b) => {
            const aPct = (a.state && a.state.batteryCharge !== undefined) ? parseFloat(a.state.batteryCharge) : 0;
            const bPct = (b.state && b.state.batteryCharge !== undefined) ? parseFloat(b.state.batteryCharge) : 0;
            if (aPct < bPct) return sortDirection === 'asc' ? -1 : 1;
            if (aPct > bPct) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
    }


    /*
    * Event listener for elements click
    */
    // Listen for input in the search field (real-time search)
    searchInput.addEventListener("input", filterTable);

    // Listen for click on the search button
    searchButton.addEventListener("click", filterTable);

    /**
     * Handle sort button click
     */
    sortButtonByPerc.addEventListener('click', () => {
            // Toggle direction
            sortDirection = (sortDirection === 'asc') ? 'desc' : 'asc';

            // Update icon
            const sortIcon = sortButtonByPerc.querySelector('i');
            if (sortDirection === 'asc') {
                sortIcon.classList.remove("fa-sort-numeric-up");
                sortIcon.classList.add("fa-sort-numeric-down");
            } else {
                sortIcon.classList.remove("fa-sort-numeric-down");
                sortIcon.classList.add("fa-sort-numeric-up");
            }

            sortData(filteredData);
            renderTable(filteredData, currentPage);
    });

    // Initial load
    if (originalBatteryData.length > 0) {
        console.log("Battery data loaded:", originalBatteryData);
        sortData(filteredData);
        renderTable(filteredData, currentPage);
    } 
    // else {
    //     console.warn("No valid battery data found:", originalBatteryData);
    //     renderTable([], 1);
    // }

});

// Add a click event listener to the button with ID 'getSmartlockData'
// document.getElementById("getSmartlockData").addEventListener("click", async function () {
//     try {
//         // Fetch the smartlock data from the PHP backend using GET request
//         const response = await fetch("../../app/controllers/BatteryController.php", {
//             method: "GET"
//         });

//         // Convert the response to JSON format
//         const data = await response.json();

//         // Get the 'result' div where data will be displayed
//         let resultDiv = document.getElementById("result");
//         resultDiv.innerHTML = ""; // Clear previous results

//         // Handle cases where the server returns an error
//         if (data.error) {
//             resultDiv.innerHTML = `Error: ${data.error}`;
//             return;
//         }

//         // If no devices are found, display a message
//         if (data.length === 0) {
//             resultDiv.innerHTML = "No devices found.";
//         } else {
//             // If data is returned, display each smartlock's information
//             data.forEach(smartlock => {
//                 let smartlockInfo = `
//                     <div class="smartlock-card mb-4 p-3 border rounded shadow-sm">
//                         <h4>Smartlock: ${smartlock.name}</h4>
//                         <p><strong>Battery Status:</strong> ${smartlock.state.batteryCritical ? "Critical" : "Normal"} (${smartlock.state.batteryCharge}%)</p>
//                         <p><strong>Device State:</strong> ${smartlock.state.state}</p>
//                     </div>
//                 `;
//                 resultDiv.innerHTML += smartlockInfo;
//             });
//         }
//     } catch (error) {
//         // Handle any errors that occur during fetch
//         console.error("Error:", error);
//         document.getElementById("result").innerHTML = "An error occurred while fetching the devices.";
//     }
// });
