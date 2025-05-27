document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const menu = document.querySelector('.menu');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            menu.classList.toggle('active');
        });
    }
    
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToCart(productId, 1);
        });
    });
    
    function addToCart(productId, quantity) {
        console.log('Adding to cart:', productId, quantity);
        // Create form data
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        // Send AJAX request
        fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            if (data.success) {
                // Update cart count
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                }
                
                // Show success message
                showMessage('Product added to cart!', 'success');
            } else {
                // Show error message
                showMessage(data.message || 'Error adding product to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error adding product to cart', 'error');
        });
    }
    
    // Cart quantity update - Unified event handlers
    if (!window.location.pathname.endsWith('product.php') && !window.location.pathname.includes('product.php')) {
        const decrementButtons = document.querySelectorAll('.decrement-btn');
        const incrementButtons = document.querySelectorAll('.increment-btn');
        const quantityInputs = document.querySelectorAll('.quantity-input');
        
        decrementButtons.forEach(button => {
            button.addEventListener('click', function() {
                console.log('Decrement button clicked');
                const itemId = this.getAttribute('data-id');
                const input = this.nextElementSibling;
                let value = parseInt(input.value);
                if (value > 1) {
                    value--;
                    input.value = value;
                    updateCartItem(itemId, value);
                }
            });
        });

        incrementButtons.forEach(button => {
            button.addEventListener('click', function() {
                console.log('Increment button clicked');
                const itemId = this.getAttribute('data-id');
                const input = this.previousElementSibling;
                let value = parseInt(input.value);
                value++;
                input.value = value;
                updateCartItem(itemId, value);
            });
        });

        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                console.log('Quantity input changed');
                const itemId = this.getAttribute('data-id');
                let value = parseInt(this.value);
                if (isNaN(value) || value < 1) {
                    value = 1;
                    this.value = value;
                }
                updateCartItem(itemId, value);
            });
        });
    }
    
    function updateCartItem(itemId, quantity) {
        console.log('Updating cart item:', itemId, quantity);
        // Create form data
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('quantity', quantity);
        
        // Log the formData entries
        for (const pair of formData.entries()) {
            console.log(pair[0], pair[1]);
        }
        
        // Send AJAX request
        fetch('update_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Update response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Update server response:', data);
            if (data.success) {
                // Update quantity display
                const quantityInput = document.querySelector(`.quantity-input[data-id="${itemId}"]`);
                if (quantityInput) {
                    quantityInput.value = quantity;
                }
                
                // Update subtotal
                const subtotalElement = document.querySelector(`#subtotal-${itemId}`);
                if (subtotalElement) {
                    subtotalElement.textContent = data.subtotal;
                }
                
                // Update cart total
                const cartTotalElement = document.querySelector('.cart-total');
                if (cartTotalElement) {
                    cartTotalElement.textContent = 'Total: ' + data.cart_total;
                }
                
                // Update cart count in header
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                }
            } else {
                showMessage(data.message || 'Error updating cart', 'error');
            }
        })
        .catch(error => {
            console.error('Update cart error:', error);
            showMessage('Error updating cart', 'error');
        });
    }
    
    // Remove cart item
    const removeButtons = document.querySelectorAll('.cart-item-remove');
    
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const cartItem = this.closest('.cart-item');
            
            removeCartItem(itemId, cartItem);
        });
    });
    
    function removeCartItem(itemId, element) {
        console.log('Removing cart item:', itemId);
        // Create form data
        const formData = new FormData();
        formData.append('item_id', itemId);
        
        // Send AJAX request
        fetch('remove_cart_item.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Remove response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Remove server response:', data);
            if (data.success) {
                // Remove cart item from DOM
                element.remove();
                
                // Update cart count in header
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.cart_count || '0';
                }
                
                // Update cart total or show empty cart message
                const cartTotalElement = document.querySelector('.cart-total');
                
                // Check if cart is empty (use server response instead of DOM)
                if (data.is_cart_empty) {
                    // Cart is empty, update the entire cart container
                    const cartContainer = document.querySelector('.cart-container');
                    if (cartContainer) {
                        cartContainer.innerHTML = '<div class="empty-cart"><h2>Your cart is empty</h2><p>Add some delicious food items to your cart!</p><a href="menu.php" class="btn btn-primary">Browse Menu</a></div>';
                    }
                } else if (cartTotalElement) {
                    // Not the last item, just update the total
                    cartTotalElement.textContent = 'Total: ' + data.cart_total;
                }
                
                showMessage('Item removed from cart', 'success');
            } else {
                showMessage(data.message || 'Error removing item from cart', 'error');
            }
        })
        .catch(error => {
            console.error('Remove cart error:', error);
            showMessage('Error removing item from cart', 'error');
        });
    }
    
    // Message display function
    function showMessage(message, type) {
        // Check if message container exists
        let messageContainer = document.querySelector('.message-container');
        
        // Create container if it doesn't exist
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'message-container';
            document.body.appendChild(messageContainer);
            
            // Style the container
            messageContainer.style.position = 'fixed';
            messageContainer.style.top = '20px';
            messageContainer.style.right = '20px';
            messageContainer.style.zIndex = '9999';
        }
        
        // Create message element
        const messageElement = document.createElement('div');
        messageElement.className = `message ${type}`;
        messageElement.textContent = message;
        
        // Style the message
        messageElement.style.padding = '15px 20px';
        messageElement.style.marginBottom = '10px';
        messageElement.style.borderRadius = '5px';
        messageElement.style.boxShadow = '0 3px 10px rgba(0, 0, 0, 0.2)';
        messageElement.style.animation = 'slideIn 0.3s ease';
        
        if (type === 'success') {
            messageElement.style.backgroundColor = '#4ecdc4';
            messageElement.style.color = 'white';
        } else {
            messageElement.style.backgroundColor = '#ff6b6b';
            messageElement.style.color = 'white';
        }
        
        // Add animation keyframes
        if (!document.querySelector('#message-animations')) {
            const style = document.createElement('style');
            style.id = 'message-animations';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Add to container
        messageContainer.appendChild(messageElement);
        
        // Remove after 3 seconds
        setTimeout(() => {
            messageElement.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                messageElement.remove();
            }, 300);
        }, 3000);
    }

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    // Add error class
                    field.classList.add('error');
                    
                    // Create error message if doesn't exist
                    let errorMessage = field.nextElementSibling;
                    if (!errorMessage || !errorMessage.classList.contains('error-message')) {
                        errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = 'This field is required';
                        field.parentNode.insertBefore(errorMessage, field.nextSibling);
                    }
                } else {
                    // Remove error class and message
                    field.classList.remove('error');
                    const errorMessage = field.nextElementSibling;
                    if (errorMessage && errorMessage.classList.contains('error-message')) {
                        errorMessage.remove();
                    }
                }
            });
            
            if (!valid) {
                e.preventDefault();
            }
        });
    });

    // Image placeholders
    const imageElements = document.querySelectorAll('.category-img, .product-img');
    imageElements.forEach(img => {
        // Check if background image is already set
        const style = window.getComputedStyle(img);
        const backgroundImage = style.backgroundImage;
        
        if (backgroundImage === 'none' || backgroundImage === '') {
            // Set placeholder image
            const type = img.classList.contains('category-img') ? 'category' : 'food';
            img.style.backgroundImage = `url('https://via.placeholder.com/300x200?text=${type}')`;
        }
    });
});