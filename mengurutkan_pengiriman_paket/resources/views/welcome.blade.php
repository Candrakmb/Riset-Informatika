<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
        
        <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
        <!-- Styles -->
    </head>
    <body>
        <div class="row p-4">
              <div class="col p-3">
                <div class="mb-3">
                    <label for="penerima" class="col-form-label">Penerima</label>
                    <input type="text" class="form-control" id="penerima">
                </div>
                <span>Cari alamat pada map berikut:</span>
                <div id="map" style="width: 600px; height: 500px;"></div>
                <br>
                <button type="button" class="btn btn-primary" id="add_data">ADD</button>
              </div>
              <div class="col">
                <table class="table table-striped table-hover" id="data-table">
                    <thead>
                        <tr>
                          <th scope="col">no</th>
                          <th scope="col">penerima</th>
                          <th scope="col">alamat</th>
                          <th scope="col">latitude</th>
                          <th scope="col">longitude</th>
                        </tr>
                      </thead>
                      <tbody>
                      </tbody>
                </table>
              </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
        <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
        <script>
            const dataPaket=[];
            var lat,lng,alamat,marker,userMarker;
            var dataMarkers = [];
            const latitudeUser = 0; // replace with the actual latitude
            const longitudeUser = 0; // replace with the actual longitude

            const map = L.map('map').setView([latitudeUser, longitudeUser], 13);

            const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            function showPosition(position) {
                // Store the user's coordinates
                const userLat = position.coords.latitude;
                const userLng = position.coords.longitude;

                // Create a marker for the user's position
                if (userMarker) {
                    map.removeLayer(userMarker);
                }
                userMarker = L.marker([userLat, userLng]).addTo(map);
                userMarker.bindPopup('Your Location').openPopup();

                // Update the map view to center on the user's position
                map.setView([userLat, userLng], 13);
            }
            // Menangani kesalahan mendapatkan posisi
            function showError(error) {
            switch (error.code) {
                case error.PERMISSION_DENIED:
                console.log("User denied the request for Geolocation.");
                break;
                case error.POSITION_UNAVAILABLE:
                console.log("Location information is unavailable.");
                break;
                case error.TIMEOUT:
                console.log("The request to get user location timed out.");
                break;
                case error.UNKNOWN_ERROR:
                console.log("An unknown error occurred.");
                break;
            }
            }

            var geocoder = L.Control.geocoder({
                defaultMarkGeocode: false,
                collapsed: false,
                placeholder: 'Masukkan Alamat',
            }).on('markgeocode', function (e) {
                // Remove the existing marker if it exists
                if (marker) {
                    map.removeLayer(marker);
                }

                var latlng = e.geocode.center;
                lat = latlng.lat;
                lng = latlng.lng;

                // Use Nominatim for reverse geocoding
                var apiUrl = `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`;

                fetch(apiUrl)
                    .then(response => response.json())
                    .then(data => {
                        var address = data.display_name;

                        alamat = address;

                        // Add a draggable marker to the selected location
                        marker = L.marker(latlng, { draggable: true }).addTo(map);
                        marker.bindPopup(address).openPopup(); // Display the address as a popup

                        // Update the marker position and address when it is dragged
                        marker.on('dragend', function (event) {
                            var draggedMarker = event.target;
                            var newLatLng = draggedMarker.getLatLng();
                            lat = newLatLng.lat;
                            lng = newLatLng.lng;
                            // Use Nominatim for reverse geocoding for the new position
                            var newApiUrl = `https://nominatim.openstreetmap.org/reverse?lat=${newLatLng.lat}&lon=${newLatLng.lng}&format=json`;

                            fetch(newApiUrl)
                                .then(response => response.json())
                                .then(newData => {
                                    var newAddress = newData.display_name;

                                    // Do something with the new address (e.g., display it on the page)
                                    alamat = newAddress;
                                    marker.bindPopup(newAddress).openPopup();
                                })
                                .catch(error => console.error('Error:', error));
                        });
                    })
                    .catch(error => console.error('Error:', error));

                map.setView(latlng, map.getZoom());
            }).addTo(map);

            function addMarker(data) {
            // Periksa apakah marker sudah ada
                var existingMarker = dataMarkers.find(function (marker) {
                    return marker.getLatLng().lat === data.latitude && marker.getLatLng().lng === data.longitude;
                });

                // Hapus semua marker dari peta
                dataMarkers.forEach(function (marker) {
                    map.removeLayer(marker);
                });

                if (!existingMarker) {
                var newMarker = L.marker([data.latitude, data.longitude]).addTo(map)
                    .bindPopup(`<b>${data.penerima}</b><br>${data.alamat}`)
                    .openPopup();
                    dataMarkers.push(newMarker);
                }

                // Tambahkan semua marker yang ada di array ke peta
                dataMarkers.forEach(function (marker) {
                    map.addLayer(marker);
                });
            }

            function populateTable(dataPaket) {
                var tableBody = document.getElementById('data-table').getElementsByTagName('tbody')[0];
                tableBody.innerHTML = '';
                dataPaket.forEach(function (data, index) {
                    var row = tableBody.insertRow();
                    
                    // Tambahkan nomor urut pada setiap baris
                    var cellNo = row.insertCell(0);
                    cellNo.textContent = index + 1;

                    for (var key in data) {
                    var cell = row.insertCell();
                    cell.textContent = data[key];
                    }

                    // Tambahkan tombol hapus dan aksi hapus pada setiap baris
                    var cellActions = row.insertCell();
                    var deleteButton = document.createElement('button');
                    deleteButton.textContent = 'Hapus';
                    deleteButton.onclick = function () {
                    deleteRow(index);
                    };
                    cellActions.appendChild(deleteButton);

                    addMarker(data);
                });
                
            }

            function deleteRow(index) {
                var deletedData = dataPaket.splice(index, 1)[0]; 
                dataMarkers.forEach(function (marker) {
                    map.removeLayer(marker);
                });
                var deletedDataMarker = dataMarkers.splice(index, 1)[0]; 
               
                populateTable(dataPaket);
            }


            function addDetailPaket() {
                const inputValue = document.querySelector('#penerima').value;
                if (inputValue.trim() === '') {
                    alert('Nama penerima masih kosong');
                } else if (!alamat && !lat && !lng ) {
                    alert('Alamat kosong, isi pada search map');
                } else {
                    const newDataPaket = {
                        penerima: inputValue,
                        alamat : alamat,
                        latitude : lat,
                        longitude : lng,
                    };
                    dataPaket.push(newDataPaket);
                    document.querySelector('#penerima').value = '';
                    alamat = '';
                    lat = '';
                    lng = '';
                    alert('data berhasil ditambahkan');
                }
                populateTable(dataPaket);
            }

            document.querySelector('#add_data').addEventListener('click', addDetailPaket);
            navigator.geolocation.getCurrentPosition(showPosition, showError, { enableHighAccuracy: true });

            

        </script>
    </body>
</html>
