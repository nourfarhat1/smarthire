class JobSearch {
    constructor() {
        this.searchInput = null;
        this.dropdown = null;
        this.currentRequest = null;
        
        this.init();
    }

    init() {
        this.searchInput = document.getElementById('search');
        if (!this.searchInput) return;

        this.createDropdown();
        this.setupEventListeners();
    }

    createDropdown() {
        // Create dropdown container
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'algolia-dropdown';
        this.dropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        `;

        // Position the dropdown relative to the search input
        this.searchInput.parentElement.style.position = 'relative';
        this.searchInput.parentElement.appendChild(this.dropdown);
    }

    setupEventListeners() {
        let debounceTimer;

        this.searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            
            // Clear previous request
            if (this.currentRequest) {
                this.currentRequest.abort();
            }

            // Debounce search
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (query.length >= 2) {
                    this.search(query);
                } else {
                    this.hideDropdown();
                }
            }, 300);
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.dropdown.contains(e.target)) {
                this.hideDropdown();
            }
        });

        // Hide dropdown on escape key
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideDropdown();
            }
        });
    }

    async search(query) {
        try {
            this.currentRequest = new AbortController();
            
            const response = await fetch(`/api/search/jobs?q=${encodeURIComponent(query)}&limit=8`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: this.currentRequest.signal
            });

            if (!response.ok) {
                throw new Error('Search request failed');
            }

            const data = await response.json();
            this.displayResults(data.hits || []);

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Search error:', error);
                this.showError('Search temporarily unavailable');
            }
        }
    }

    displayResults(hits) {
        if (hits.length === 0) {
            this.showNoResults();
            return;
        }

        const html = hits.map(hit => `
            <div class="algolia-result" data-job-id="${hit.objectID}">
                <div class="result-title">
                    ${this.highlightText(hit._highlightResult?.title?.value || hit.title)}
                </div>
                <div class="result-description">
                    ${this.snippetText(hit._snippetResult?.description?.value || hit.description)}
                </div>
                <div class="result-meta">
                    <span class="location">${hit.location || 'Location not specified'}</span>
                    ${hit.category ? `<span class="category">${hit.category}</span>` : ''}
                </div>
            </div>
        `).join('');

        this.dropdown.innerHTML = html;
        this.showDropdown();

        // Add click handlers to results
        this.dropdown.querySelectorAll('.algolia-result').forEach(result => {
            result.addEventListener('click', () => {
                const jobId = result.dataset.jobId;
                window.location.href = `/candidate/applications/apply/${jobId}`;
            });
        });
    }

    showNoResults() {
        this.dropdown.innerHTML = `
            <div class="algolia-no-results">
                <div class="no-results-icon">🔍</div>
                <div class="no-results-text">No jobs found matching your search</div>
            </div>
        `;
        this.showDropdown();
    }

    showError(message) {
        this.dropdown.innerHTML = `
            <div class="algolia-error">
                <div class="error-icon">⚠️</div>
                <div class="error-text">${message}</div>
            </div>
        `;
        this.showDropdown();
    }

    highlightText(text) {
        if (!text) return '';
        return text.replace(/__ais-highlight__/g, '<strong>').replace(/__\/ais-highlight__/g, '</strong>');
    }

    snippetText(text) {
        if (!text) return '';
        return text.replace(/__ais-highlight__/g, '<strong>').replace(/__\/ais-highlight__/g, '</strong>');
    }

    showDropdown() {
        this.dropdown.style.display = 'block';
    }

    hideDropdown() {
        this.dropdown.style.display = 'none';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new JobSearch();
});
