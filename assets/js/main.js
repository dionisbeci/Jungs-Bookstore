// Load best sellers on the home page
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('bestSellers')) {
        loadBestSellers();
    }

    // Add event delegation for wishlist buttons
    document.addEventListener('click', function(e) {
        // Check if the click was on the button or the heart icon
        const wishlistBtn = e.target.closest('.wishlist-btn');
        if (wishlistBtn) {
            e.preventDefault(); // Prevent any default button behavior
            const bookId = wishlistBtn.getAttribute('data-book-id');
            console.log('Wishlist button clicked, bookId:', bookId);
            if (bookId) {
                addToWishlist(bookId);
            }
        }
    });
});

// Function to load best sellers
function loadBestSellers() {
    fetch('api/get_best_sellers.php')
        .then(response => response.json())
        .then(data => {
            const bestSellersContainer = document.getElementById('bestSellers');
            bestSellersContainer.innerHTML = '';
            
            data.forEach(book => {
                const bookCard = createBookCard(book);
                bestSellersContainer.appendChild(bookCard);
            });
        })
        .catch(error => console.error('Error loading best sellers:', error));
}

// Create a book card element
function createBookCard(book) {
    const col = document.createElement('div');
    col.className = 'col-md-3 col-sm-6 mb-4';
    
    col.innerHTML = `
        <div class="card book-card h-100">
            <img src="${book.cover_image || 'assets/images/default-book-cover.jpg'}" 
                 class="card-img-top" 
                 alt="${book.title}">
            <div class="card-body">
                <h5 class="card-title">${book.title}</h5>
                <p class="card-text">By ${book.author}</p>
                <p class="card-text">$${book.price}</p>
                <button onclick="addToCart(${book.id})" class="btn btn-primary">Add to Cart</button>
                ${isLoggedIn ? `<button onclick="addToWishlist(${book.id})" 
                    class="btn btn-outline-secondary wishlist-btn" 
                    data-book-id="${book.id}">
                    <i class="fas fa-heart ${book.in_wishlist ? 'text-danger' : ''}"></i>
                </button>` : ''}
            </div>
        </div>
    `;
    
    return col;
}

// Add to cart functionality
function addToCart(bookId) {
    if (!isLoggedIn) {
        window.location.href = 'login.php';
        return;
    }
    
    fetch('api/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ book_id: bookId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Book added to cart successfully!');
            updateCartCount();
        } else {
            alert(data.message || 'Error adding book to cart');
        }
    })
    .catch(error => console.error('Error:', error));
}

// Add to wishlist functionality
function addToWishlist(bookId) {
    console.log('addToWishlist called with bookId:', bookId);
    
    // Find the wishlist button for this book
    const wishlistBtn = document.querySelector(`.wishlist-btn[data-book-id="${bookId}"]`);
    if (!wishlistBtn) {
        console.log('Wishlist button not found');
        return;
    }

    const heartIcon = wishlistBtn.querySelector('i.fa-heart');
    if (!heartIcon) {
        console.log('Heart icon not found');
        return;
    }

    // Optimistically update the UI
    heartIcon.classList.toggle('text-danger');

    console.log('Sending request to add_to_wishlist.php');
    fetch('api/add_to_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ book_id: bookId })
    })
    .then(response => {
        console.log('Response received:', response);
        return response.json();
    })
    .then(data => {
        console.log('Data received:', data);
        if (data.success) {
            // Show a subtle notification
            const notification = document.createElement('div');
            notification.className = 'alert alert-success position-fixed top-0 end-0 m-3';
            notification.style.zIndex = '1000';
            notification.textContent = data.message;
            document.body.appendChild(notification);
            
            // Remove notification after 2 seconds
            setTimeout(() => {
                notification.remove();
            }, 2000);
        } else {
            // Revert the heart color if there was an error
            heartIcon.classList.toggle('text-danger');
            alert(data.message || 'Error updating wishlist');
        }
    })
    .catch(error => {
        console.error('Error in wishlist request:', error);
        // Revert the heart color if there was an error
        heartIcon.classList.toggle('text-danger');
        alert('Error updating wishlist');
    });
}

// Update cart count in navigation
function updateCartCount() {
    fetch('api/get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const cartCount = document.getElementById('cartCount');
            if (cartCount) {
                cartCount.textContent = data.count;
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}

// Search functionality
function searchBooks(query) {
    window.location.href = `books.php?search=${encodeURIComponent(query)}`;
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const inputs = form.querySelectorAll('input[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Image preview for file inputs
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            if (preview) {
                preview.src = e.target.result;
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Star rating functionality
function setRating(rating) {
    document.getElementById('ratingInput').value = rating;
    const stars = document.querySelectorAll('.star-rating i');
    stars.forEach((star, index) => {
        star.classList.toggle('fas', index < rating);
        star.classList.toggle('far', index >= rating);
    });
} 