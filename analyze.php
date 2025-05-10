<?php
require_once 'includes/header.php';
require_once 'functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$dataset_id = (int)$_GET['id'];
$analysis = analyzeDataset($dataset_id);

if (!$analysis) {
    echo '<div class="alert alert-danger">Dataset not found</div>';
    require_once 'includes/footer.php';
    exit();
}

$dataset = $analysis['dataset_info'];
$columns = $analysis['columns'];
$stats = $analysis['stats'];
$timeAnalysis = $analysis['time_analysis'];
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        <?php echo htmlspecialchars($dataset['name']); ?>
                    </h5>
                    <div>
                        <span class="badge bg-primary">
                            <?php echo number_format($dataset['record_count']); ?> records
                        </span>
                        <span class="badge bg-secondary ms-2">
                            <?php echo count($columns); ?> columns
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <p class="card-text"><?php echo htmlspecialchars($dataset['description']); ?></p>
                <p class="text-muted small mb-0">
                    Uploaded: <?php echo date('M d, Y H:i', strtotime($dataset['upload_date'])); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="fas fa-list me-2"></i>Dataset Columns</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach($columns as $column): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <code><?php echo $column; ?></code>
                            <?php if(isset($stats[$column])): ?>
                            <span class="badge bg-info">numeric</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="fas fa-calculator me-2"></i>Numeric Statistics</h6>
            </div>
            <div class="card-body">
                <?php if(!empty($stats)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Column</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Average</th>
                                <th>Sum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stats as $column => $stat): ?>
                            <tr>
                                <td><code><?php echo $column; ?></code></td>
                                <td><?php echo number_format($stat['min'], 2); ?></td>
                                <td><?php echo number_format($stat['max'], 2); ?></td>
                                <td><?php echo number_format($stat['avg'], 2); ?></td>
                                <td><?php echo number_format($stat['sum'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">No numeric columns found for statistical analysis</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($timeAnalysis)): ?>
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Advanced Analysis</h6>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" id="addSeries">Add Series</button>
                        <button class="btn btn-sm btn-outline-secondary" id="resetZoom">Reset Zoom</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="chart-container" style="position: relative; height:400px;">
                            <canvas id="multiSeriesChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div id="correlationMatrix" class="mb-4"></div>
                        <div id="columnSelector">
                            <h6>Available Columns</h6>
                            <?php foreach($columns as $column): ?>
                            <div class="form-check">
                                <input class="form-check-input column-toggle" type="checkbox" value="<?php echo $column; ?>" id="col_<?php echo $column; ?>">
                                <label class="form-check-label" for="col_<?php echo $column; ?>">
                                    <?php echo $column; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="fas fa-map-marker-alt me-2"></i>Geographic Analysis</h6>
            </div>
            <div class="card-body">
                <div id="map" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map    const map = L.map('map').setView([39.8283, -98.5795], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const markers = L.markerClusterGroup(); // Add marker clustering
    
    // Load location data
    fetch('api/get_location_data.php?id=<?php echo $dataset_id; ?>')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.locations || data.locations.length === 0) {
                document.getElementById('map').innerHTML = '<div class="alert alert-info">No location data available.</div>';
                return;
            }

            data.locations.forEach(loc => {
                if (loc.lat && loc.lon) {
                    const marker = L.marker([loc.lat, loc.lon])
                        .bindPopup(`
                            <div class="p-2">
                                <h6 class="mb-1">${loc.city}, ${loc.state}</h6>
                                <p class="mb-1">Zipcode: ${loc.zipcode}</p>
                                <hr class="my-2">
                                <p class="mb-0">
                                    <strong>Annual Consumption:</strong> ${loc.annual_consume ? loc.annual_consume.toLocaleString() + ' kWh' : 'N/A'}<br>
                                    <strong>Low Tariff:</strong> ${loc.annual_consume_lowtarif_perc ? loc.annual_consume_lowtarif_perc + '%' : 'N/A'}
                                </p>
                            </div>
                        `);
                    markers.addLayer(marker);
                }
            });
            
            map.addLayer(markers);
            
            if (markers.getLayers().length > 0) {
                map.fitBounds(markers.getBounds());
            }
        })
        .catch(error => {
            console.error('Error loading location data:', error);
            document.getElementById('map').innerHTML = '<div class="alert alert-danger">Error loading location data.</div>';
        });

    // Initialize multi-series chart
    const ctx = document.getElementById('multiSeriesChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($timeAnalysis['daily'], 'day')); ?>,
            datasets: [{
                label: 'Consumption',
                data: <?php echo json_encode(array_column($timeAnalysis['daily'], 'avg_consumption')); ?>,
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                zoom: {
                    zoom: {
                        wheel: {
                            enabled: true
                        },
                        pinch: {
                            enabled: true
                        },
                        mode: 'xy'
                    },
                    pan: {
                        enabled: true,
                        mode: 'xy'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });

    // Reset zoom button handler
    document.getElementById('resetZoom').addEventListener('click', () => {
        chart.resetZoom();
    });

    // Create correlation matrix
    const numericColumns = <?php 
        echo json_encode(array_keys($stats)); 
    ?>;    // Fetch correlation data for numeric columns
    fetch(`api/get_correlations.php?id=<?php echo $dataset_id; ?>`)
        .then(response => response.json())
        .then(data => {
            const correlationData = data.correlations;
            if (!correlationData || correlationData.length === 0) {
                document.getElementById('correlationMatrix').innerHTML = 
                    '<div class="alert alert-info">No correlation data available.</div>';
                return;
            }            const margin = { top: 60, right: 50, bottom: 60, left: 50 };
            const size = 400;

            // Clear previous visualization
            d3.select("#correlationMatrix").html("");

            const svg = d3.select("#correlationMatrix")
                .append("svg")
                .attr("width", size + margin.left + margin.right)
                .attr("height", size + margin.top + margin.bottom)
                .append("g")
                .attr("transform", `translate(${margin.left},${margin.top})`);

            const x = d3.scaleBand()
                .range([0, size])
                .domain(data.columns);

            const y = d3.scaleBand()
                .range([0, size])
                .domain(data.columns);

            const color = d3.scaleLinear()
                .domain([-1, 0, 1])
                .range(["#ff4e4e", "#ffffff", "#4e89ff"]);

            // Create tooltip
            const tooltip = d3.select("body")
                .append("div")
                .attr("class", "correlation-tooltip")
                .style("opacity", 0);

            // Add correlation cells
            svg.selectAll()
                .data(data.correlations.flat())
                .enter()
                .append("rect")
                .attr("class", "correlation-cell")
                .attr("x", (d, i) => x(data.columns[Math.floor(i / data.columns.length)]))
                .attr("y", (d, i) => y(data.columns[i % data.columns.length]))                .attr("width", x.bandwidth())
                .attr("height", y.bandwidth())
                .style("fill", d => color(d))
                .on("mouseover", function(event, d) {
                    const i = Math.floor(event.target.attributes.x.value / x.bandwidth());
                    const j = Math.floor(event.target.attributes.y.value / y.bandwidth());
                    const col1 = data.columns[i];
                    const col2 = data.columns[j];
                    
                    tooltip.transition()
                        .duration(200)
                        .style("opacity", .9);
                    tooltip.html(`
                        <strong>${col1}</strong> vs <strong>${col2}</strong><br/>
                        Correlation: ${d.toFixed(3)}
                    `)
                    .style("left", (event.pageX + 10) + "px")
                    .style("top", (event.pageY - 28) + "px");
                    
                    d3.select(this)
                        .style("stroke", "black")
                        .style("stroke-width", 2);
                })
                .on("mouseout", function() {
                    tooltip.transition()
                        .duration(500)
                        .style("opacity", 0);
                    
                    d3.select(this)
                        .style("stroke", "none");
                });

            // Add X axis labels
            svg.append("g")
                .attr("transform", `translate(0,${size})`)
                .call(d3.axisBottom(x))
                .selectAll("text")
                .attr("transform", "rotate(-45)")
                .style("text-anchor", "end");

            // Add Y axis labels
            svg.append("g")
                .call(d3.axisLeft(y));

            // Add title
            svg.append("text")
                .attr("x", size / 2)
                .attr("y", -20)
                .attr("text-anchor", "middle")
                .style("font-size", "16px")
                .text("Correlation Matrix");

            // Add legend
            const legendWidth = 200;
            const legendHeight = 10;

            const legendScale = d3.scaleLinear()
                .domain([-1, 1])
                .range([0, legendWidth]);

            const legendAxis = d3.axisBottom(legendScale)
                .ticks(5)
                .tickFormat(d3.format(".1f"));

            const legend = svg.append("g")
                .attr("transform", `translate(${(size - legendWidth) / 2},${size + 50})`);

            const defs = svg.append("defs");

            const linearGradient = defs.append("linearGradient")
                .attr("id", "correlation-gradient");

            linearGradient.selectAll("stop")
                .data([
                    {offset: "0%", color: "#ff4e4e"},
                    {offset: "50%", color: "#ffffff"},
                    {offset: "100%", color: "#4e89ff"}
                ])
                .enter().append("stop")
                .attr("offset", d => d.offset)
                .attr("stop-color", d => d.color);

            legend.append("rect")
                .attr("width", legendWidth)
                .attr("height", legendHeight)
                .style("fill", "url(#correlation-gradient)");

            legend.append("g")
                .attr("transform", `translate(0,${legendHeight})`)
                .call(legendAxis);

    // Column selection and series addition
    document.getElementById('addSeries').addEventListener('click', () => {
        const checkedColumns = Array.from(document.querySelectorAll('.column-toggle:checked'))
            .map(checkbox => checkbox.value);
        
        if (checkedColumns.length > 0) {
            // Here you would fetch the data for the selected columns
            // and add them as new series to the chart
            checkedColumns.forEach(column => {
                const newData = Array(chart.data.labels.length).fill(null)
                    .map(() => Math.random() * 100); // Replace with actual data

                chart.data.datasets.push({
                    label: column,
                    data: newData,
                    borderColor: `rgb(${Math.random() * 255},${Math.random() * 255},${Math.random() * 255})`,
                    tension: 0.1
                });
            });
            chart.update();
        }
    });
});
</script>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0"><i class="fas fa-table me-2"></i>Sample Data</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <?php foreach($columns as $column): ?>
                                <th><?php echo $column; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sampleData = getDatasetData($dataset_id, 10);
                            foreach($sampleData as $row): ?>
                            <tr>
                                <?php foreach($columns as $column): ?>
                                <td><?php echo isset($row[$column]) ? htmlspecialchars(substr($row[$column], 0, 50)) : ''; ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>