<?php
/**
 * Panda Realty - Multi-Criteria Search & Filter Component
 * Designed & Developed by TekTrend
 */
?>

<!-- Search Bar Section -->
<section class="search-container" id="searchSection">
    <div class="search-wrapper">
        <form class="search-form" action="<?= htmlspecialchars(app_path('properties')) ?>" method="GET">
            <div class="search-field">
                <label><i class="fas fa-map-marker-alt"></i> Location in Eldoret</label>
                <input type="text" name="location" placeholder="e.g. Annex, Elgon View, Pioneer, Kapsoya..." value="<?= htmlspecialchars($_GET['location'] ?? '') ?>">
            </div>

            <div class="search-field">
                <label><i class="fas fa-building"></i> Property Type</label>
                <select name="type">
                    <option value="">All Property Types</option>
                    <option value="studio" <?= (isset($_GET['type']) && $_GET['type'] === 'studio') ? 'selected' : '' ?>>Studio Apartments</option>
                    <option value="house" <?= (isset($_GET['type']) && $_GET['type'] === 'house') ? 'selected' : '' ?>>Residential Houses</option>
                    <option value="villa" <?= (isset($_GET['type']) && $_GET['type'] === 'villa') ? 'selected' : '' ?>>Luxury Villas</option>
                    <option value="apartment" <?= (isset($_GET['type']) && $_GET['type'] === 'apartment') ? 'selected' : '' ?>>Apartments & Flats</option>
                    <option value="land" <?= (isset($_GET['type']) && $_GET['type'] === 'land') ? 'selected' : '' ?>>Land & Titled Plots</option>
                    <option value="commercial" <?= (isset($_GET['type']) && $_GET['type'] === 'commercial') ? 'selected' : '' ?>>Commercial Real Estate</option>
                </select>
            </div>

            <div class="search-field">
                <label><i class="fas fa-tag"></i> Purpose</label>
                <select name="category">
                    <option value="">Buy or Rent</option>
                    <option value="sale" <?= (isset($_GET['category']) && $_GET['category'] === 'sale') ? 'selected' : '' ?>>For Sale (Outright & Installments)</option>
                    <option value="rent" <?= (isset($_GET['category']) && $_GET['category'] === 'rent') ? 'selected' : '' ?>>For Rent / Lease</option>
                </select>
            </div>

            <div class="search-field">
                <label><i class="fas fa-bed"></i> Bedrooms</label>
                <select name="bedrooms">
                    <option value="">Any</option>
                    <option value="studio">Studio (Single Unit)</option>
                    <option value="1">1+ Bedroom</option>
                    <option value="2">2+ Bedrooms</option>
                    <option value="3">3+ Bedrooms</option>
                    <option value="4">4+ Bedrooms</option>
                    <option value="5">5+ Bedrooms</option>
                </select>
            </div>

            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search Properties
            </button>
        </form>
    </div>
</section>

<!-- Quick Filter Tabs -->
<section class="filters">
    <div class="filters-wrapper">
        <button type="button" class="filter-tag active" data-filter="all">
            <i class="fas fa-th-large"></i> All Properties
        </button>
        <button type="button" class="filter-tag studio-tag" data-filter="studio">
            <i class="fas fa-door-open"></i> Studio Apartments
        </button>
        <button type="button" class="filter-tag" data-filter="sale">
            <i class="fas fa-home"></i> For Sale
        </button>
        <button type="button" class="filter-tag" data-filter="rent">
            <i class="fas fa-key"></i> For Rent
        </button>
        <button type="button" class="filter-tag" data-filter="land">
            <i class="fas fa-map"></i> Land & Plots
        </button>
        <button type="button" class="filter-tag" data-filter="construction">
            <i class="fas fa-hard-hat"></i> Under Construction
        </button>
        <button type="button" class="filter-tag" data-filter="completed">
            <i class="fas fa-check-circle"></i> Ready / Completed
        </button>
    </div>
</section>
