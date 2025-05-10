<?php
// Set error reporting based on environment
if ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], 'dev.') === 0) {
    // Development environment: Show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production environment: Log errors but don't display them
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', 'error.log');
}

require_once 'includes/header.php';
require_once 'functions.php';

$datasets = getDatasets();

?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-tachometer-alt me-2"></i>Energy Consumption Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Datasets</h6>
                                        <h2 class="mb-0"><?php echo count($datasets); ?></h2>
                                    </div>
                                    <i class="fas fa-database fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Latest Dataset</h6>
                                        <h5 class="mb-0">
                                            <?php echo !empty($datasets) ? $datasets[0]['name'] : 'N/A'; ?>
                                        </h5>
                                    </div>
                                    <i class="fas fa-clock fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Total Records</h6>
                                        <h2 class="mb-0">
                                            <?php 
                                            $total = 0;
                                            foreach ($datasets as $ds) {
                                                $total += $ds['record_count'];
                                            }
                                            echo number_format($total);
                                            ?>
                                        </h2>
                                    </div>
                                    <i class="fas fa-chart-bar fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Last Updated</h6>
                                        <h5 class="mb-0">
                                            <?php echo !empty($datasets) ? date('M d, Y', strtotime($datasets[0]['upload_date'])) : 'N/A'; ?>
                                        </h5>
                                    </div>
                                    <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-10">
        <div class="card shadow-sm mb-4">            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Annual Energy Consumption by Location</h5>
                    <div class="btn-group">
                        <select class="form-select form-select-sm" id="limitSelect">
                            <option value="10">Top 10</option>
                            <option value="20">Top 20</option>
                            <option value="50">Top 50</option>
                            <option value="100">Top 100</option>
                        </select>
                        <select class="form-select form-select-sm ms-2" id="sortSelect">
                            <option value="consumption">Sort by Consumption</option>
                            <option value="lowtariff">Sort by Low Tariff %</option>
                            <option value="zipcode">Sort by Zipcode</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="consumptionChart" height="300"></canvas>
                <div class="mt-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="dataTable" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Zipcode</th>
                                    <th>Annual Consumption (kWh)</th>
                                    <th>Low Tariff %</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Recent Datasets</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach(array_slice($datasets, 0, 5) as $dataset): ?>
                    <a href="analyze.php?id=<?php echo $dataset['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo $dataset['name']; ?></h6>
                            <small><?php echo date('M d', strtotime($dataset['upload_date'])); ?></small>
                        </div>
                        <p class="mb-1 text-muted small"><?php echo substr($dataset['description'], 0, 60); ?>...</p>
                        <small class="text-muted"><?php echo number_format($dataset['record_count']); ?> records</small>
                    </a>
                    <?php endforeach; ?>
                    <?php if(empty($datasets)): ?>
                    <div class="list-group-item">
                        <p class="mb-0 text-muted">No datasets available. Upload your first dataset to get started.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-table me-2"></i>All Datasets</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-upload me-1"></i> Upload New
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Records</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($datasets as $dataset): ?>
                            <tr>
                                <td><?php echo $dataset['id']; ?></td>
                                <td><?php echo $dataset['name']; ?></td>
                                <td><?php echo substr($dataset['description'], 0, 50); ?>...</td>
                                <td><?php echo number_format($dataset['record_count']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($dataset['upload_date'])); ?></td>
                                <td>
                                    <a href="analyze.php?id=<?php echo $dataset['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-chart-bar"></i> Analyze
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($datasets)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No datasets available. Upload your first dataset to get started.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let chartData = null;
    
    // Function to update chart
    function updateChart(datasetId = null) {
        const url = datasetId 
            ? `api/get_dataset_data.php?id=${datasetId}`
            : 'api/get_dataset_data.php';
            
        fetch(url)
            .then(response => response.json())
            .then(data => {
                chartData = data;
                if (chart) {
                    chart.destroy();
                }
                displayData();
                
                const ctx = document.getElementById('consumptionChart').getContext('2d');                chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Annual Consumption (kWh)',
                            data: data.consumption,
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            yAxisID: 'y'
                        }, {
                            label: 'Low Tariff Percentage (%)',
                            data: data.lowTariffPerc,
                            backgroundColor: 'rgba(75, 192, 192, 0.7)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            type: 'line',
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },                        scales: {
                            y: {
                                type: 'linear',
                                position: 'left',
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Annual Consumption (kWh)'
                                }
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                beginAtZero: true,
                                max: 100,
                                title: {
                                    display: true,
                                    text: 'Low Tariff Percentage (%)'
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Zipcode'
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error loading chart data:', error));
    }    function displayData() {
        const limit = parseInt(document.getElementById('limitSelect').value);
        const sortBy = document.getElementById('sortSelect').value;
        
        // Sort data
        const indices = [...Array(chartData.labels.length).keys()];
        indices.sort((a, b) => {
            if (sortBy === 'consumption') {
                return chartData.consumption[b] - chartData.consumption[a];
            } else if (sortBy === 'lowtariff') {
                return chartData.lowTariffPerc[b] - chartData.lowTariffPerc[a];
            } else {
                return chartData.labels[a].localeCompare(chartData.labels[b]);
            }
        });
        
        // Limit data
        const limitedIndices = indices.slice(0, limit);
        
        // Prepare chart data
        const chartLabels = limitedIndices.map(i => chartData.labels[i]);
        const chartConsumption = limitedIndices.map(i => chartData.consumption[i]);
        const chartLowTariff = limitedIndices.map(i => chartData.lowTariffPerc[i]);
        
        // Update chart
        const ctx = document.getElementById('consumptionChart').getContext('2d');
        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Annual Consumption (kWh)',
                    data: chartConsumption,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Low Tariff Percentage (%)',
                    data: chartLowTariff,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                if (context.datasetIndex === 0) {
                                    return `Consumption: ${value.toLocaleString()} kWh`;
                                } else {
                                    return `Low Tariff: ${value.toFixed(1)}%`;
                                }
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Annual Consumption (kWh)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Low Tariff Percentage (%)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Zipcode'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
        
        // Update table
        const tbody = document.getElementById('dataTable').querySelector('tbody');
        tbody.innerHTML = '';
        limitedIndices.forEach(i => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${chartData.labels[i]}</td>
                <td>${chartData.consumption[i].toLocaleString()} kWh</td>
                <td>${chartData.lowTariffPerc[i].toFixed(1)}%</td>
                <td>
                    <a href="analyze.php?id=${currentDatasetId}&zipcode=${chartData.labels[i]}" 
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-search"></i> Details
                    </a>
                </td>
            `;
            tbody.appendChild(tr);
        });
        document.getElementById('dataTable').style.display = 'table';
    }
    
    let chart = null;
    let currentDatasetId = null;
    updateChart();
    
    // Add event listeners
    document.getElementById('limitSelect').addEventListener('change', displayData);
    document.getElementById('sortSelect').addEventListener('change', displayData);
    
    document.querySelectorAll('[data-dataset-id]').forEach(element => {
        element.addEventListener('click', (e) => {
            e.preventDefault();
            currentDatasetId = e.currentTarget.dataset.datasetId;
            updateChart(currentDatasetId);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>