// DOM Elements
const photoInput = document.getElementById('photoInput');
const filePreview = document.getElementById('filePreview');
const uploadBtn = document.getElementById('uploadBtn');
const uploadForm = document.getElementById('uploadForm');
const messageContainer = document.getElementById('messageContainer');
const photoGallery = document.getElementById('photoGallery');
const galleryGrid = document.getElementById('galleryGrid');
const photoCount = document.getElementById('photoCount');

// Configuration
const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
const maxSize = 5 * 1024 * 1024; // 5MB

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadPhotos();
});

// File input change handler
photoInput.addEventListener('change', function() {
    const file = this.files[0];
    
    if (file) {
        displayFilePreview(file);
        validateFile(file);
    } else {
        hideFilePreview();
        uploadBtn.disabled = true;
    }
});

// Form submit handler
uploadForm.addEventListener('submit', function(e) {
    e.preventDefault();
    uploadFile();
});

// Display file preview
function displayFilePreview(file) {
    const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
    
    filePreview.innerHTML = `
        <strong>File terpilih:</strong> ${file.name}<br>
        <strong>Ukuran:</strong> ${fileSizeMB} MB<br>
        <strong>Tipe:</strong> ${file.type}
    `;
    filePreview.style.display = 'block';
}

// Hide file preview
function hideFilePreview() {
    filePreview.style.display = 'none';
}

// Validate selected file
function validateFile(file) {
    let isValid = true;
    let errorMessage = '';
    
    // Validate file size
    if (file.size > maxSize) {
        errorMessage += '<br><span style="color: red;">⚠️ File terlalu besar! Maksimal 5MB</span>';
        isValid = false;
    }
    
    // Validate file type
    if (!allowedTypes.includes(file.type)) {
        errorMessage += '<br><span style="color: red;">⚠️ Tipe file tidak diizinkan!</span>';
        isValid = false;
    }
    
    if (errorMessage) {
        filePreview.innerHTML += errorMessage;
    }
    
    uploadBtn.disabled = !isValid;
}

// Upload file using AJAX
async function uploadFile() {
    const file = photoInput.files[0];
    
    if (!file) {
        showMessage('Pilih file terlebih dahulu', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('photo', file);
    formData.append('ajax_upload', '1');
    
    // Show loading state
    setUploadButtonLoading(true);
    
    try {
        const response = await fetch('upload.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Get response text first to debug
        const responseText = await response.text();
        console.log('Server response:', responseText);
        
        // Try to parse JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Server mengembalikan response yang tidak valid');
        }
        
        if (result.success) {
            showMessage(result.message, 'success');
            resetForm();
            await loadPhotos(); // Reload photo gallery
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        console.error('Upload error:', error);
        showMessage('Terjadi kesalahan saat mengupload file: ' + error.message, 'error');
    } finally {
        setUploadButtonLoading(false);
    }
}

// Set upload button loading state
function setUploadButtonLoading(loading) {
    if (loading) {
        uploadBtn.innerHTML = '<span class="loading-spinner"></span> Mengupload...';
        uploadBtn.disabled = true;
        uploadBtn.classList.add('loading');
    } else {
        uploadBtn.innerHTML = 'Upload Foto';
        uploadBtn.classList.remove('loading');
        // Don't enable button if no file is selected
        uploadBtn.disabled = !photoInput.files[0];
    }
}

// Reset form after successful upload
function resetForm() {
    uploadForm.reset();
    hideFilePreview();
    uploadBtn.disabled = true;
}

// Show message to user
function showMessage(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${type}`;
    alertDiv.innerHTML = message;
    
    // Clear previous messages
    messageContainer.innerHTML = '';
    messageContainer.appendChild(alertDiv);
    
    // Auto hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    // Scroll to top to show message
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Load photos from server
async function loadPhotos() {
    try {
        const response = await fetch('upload.php?action=get_photos');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('Photos response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Server mengembalikan response yang tidak valid');
        }
        
        if (data.success !== false && data.photos && data.photos.length > 0) {
            displayPhotos(data.photos);
            photoGallery.style.display = 'block';
        } else {
            photoGallery.style.display = 'none';
        }
    } catch (error) {
        console.error('Error loading photos:', error);
        // Don't show error to user for this, just log it
    }
}

// Display photos in gallery
function displayPhotos(photos) {
    galleryGrid.innerHTML = '';
    photoCount.textContent = photos.length;
    
    photos.forEach(photo => {
        const photoItem = createPhotoItem(photo);
        galleryGrid.appendChild(photoItem);
    });
}

// Create photo item element
function createPhotoItem(photo) {
    const photoItem = document.createElement('div');
    photoItem.className = 'photo-item';
    
    photoItem.innerHTML = `
        <img src="uploads/${photo}" alt="${photo}" loading="lazy">
        <div class="photo-info">
            <div class="photo-name">${photo}</div>
        </div>
    `;
    
    // Add click handler to view full size image
    photoItem.addEventListener('click', function() {
        viewFullImage(`uploads/${photo}`, photo);
    });
    
    return photoItem;
}

// View full size image in modal (simple implementation)
function viewFullImage(src, name) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        cursor: pointer;
    `;
    
    const img = document.createElement('img');
    img.src = src;
    img.alt = name;
    img.style.cssText = `
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        border-radius: 10px;
    `;
    
    modal.appendChild(img);
    document.body.appendChild(modal);
    
    // Close modal on click
    modal.addEventListener('click', function() {
        document.body.removeChild(modal);
    });
    
    // Close modal on escape key
    const handleEscape = function(e) {
        if (e.key === 'Escape') {
            document.body.removeChild(modal);
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

// Drag and drop functionality
const uploadForm_element = document.querySelector('.upload-form');

uploadForm_element.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.borderColor = '#667eea';
    this.style.background = '#f0f4ff';
});

uploadForm_element.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.style.borderColor = '#dee2e6';
    this.style.background = '#f8f9fa';
});

uploadForm_element.addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.borderColor = '#dee2e6';
    this.style.background = '#f8f9fa';
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        photoInput.files = files;
        // Trigger change event
        const event = new Event('change', { bubbles: true });
        photoInput.dispatchEvent(event);
    }
});
