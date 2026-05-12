// Simple search dropdown for debugging
console.log('Search script loaded');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing search...');
    
    const searchInput = document.getElementById('search');
    console.log('Search input found:', searchInput);
    
    if (!searchInput) {
        console.error('Search input not found!');
        return;
    }

    // Create dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'search-dropdown';
    dropdown.style.cssText = `
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

    // Position dropdown
    searchInput.parentElement.style.position = 'relative';
    searchInput.parentElement.appendChild(dropdown);

    let searchTimeout;

    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        console.log('Search query:', query);
        
        clearTimeout(searchTimeout);
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        } else {
            dropdown.style.display = 'none';
        }
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    async function performSearch(query) {
        console.log('Performing search for:', query);
        
        try {
            const response = await fetch(`/api/search/jobs?q=${encodeURIComponent(query)}&limit=5`);
            console.log('Search response status:', response.status);
            
            if (!response.ok) {
                throw new Error('Search failed');
            }
            
            const data = await response.json();
            console.log('Search results:', data);
            
            displayResults(data.hits || []);
            
        } catch (error) {
            console.error('Search error:', error);
            dropdown.innerHTML = '<div style="padding: 20px; text-align: center; color: #dc3545;">Search error</div>';
            dropdown.style.display = 'block';
        }
    }

    function displayResults(hits) {
        console.log('Displaying results:', hits);
        
        if (hits.length === 0) {
            dropdown.innerHTML = '<div style="padding: 20px; text-align: center; color: #6c757d;">No results found</div>';
        } else {
            dropdown.innerHTML = hits.map(hit => `
                <div style="padding: 12px 16px; border-bottom: 1px solid #f0f0f0; cursor: pointer;" onclick="window.location.href='/candidate/applications/apply/${hit.objectID}'">
                    <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">${hit.title}</div>
                    <div style="color: #6c757d; font-size: 13px; margin-bottom: 6px;">${hit.description ? hit.description.substring(0, 100) + '...' : ''}</div>
                    <div style="font-size: 12px; color: #868e96;">
                        <span style="margin-right: 12px;">📍 ${hit.location || 'Not specified'}</span>
                        ${hit.category ? `<span style="background: #e9ecef; padding: 2px 6px; border-radius: 4px;">${hit.category}</span>` : ''}
                    </div>
                </div>
            `).join('');
        }
        
        dropdown.style.display = 'block';
    }
});
