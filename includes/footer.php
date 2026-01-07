    <script>
        // Common JavaScript functions
        
        // Confirm delete
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete?');
        }
        
        // Show alert
        function showAlert(message, type) {
            alert(message);
        }
        
        // Format currency
        function formatCurrency(amount) {
            return '$' + parseFloat(amount).toFixed(2);
        }
    </script>
</body>
</html>