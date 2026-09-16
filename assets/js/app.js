// Utility functions
const fetchAPI = async (url, options = {}) => {
    try {
        const response = await fetch(url, options);
        if (!response.ok) throw new Error('Error en red');
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        return { success: false, message: 'Error de conexión' };
    }
};

const showToast = (message, icon = 'success') => {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
    Toast.fire({ icon: icon, title: message });
};

// Formato de fecha
const formatTime = (dateStr) => {
    const d = new Date(dateStr);
    return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
};
