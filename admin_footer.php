    </main>
</div>

<script>
// Global Admin JS handles
function toggleSelectAll(master) {
    let checkbxes = document.getElementsByClassName('admin-checkbox');
    for (let cb of checkbxes) cb.checked = master.checked;
    updateBulkBar();
}

function updateBulkBar() {
    let count = document.querySelectorAll('.admin-checkbox:checked').length;
    let bar = document.getElementById('bulkBar');
    if(bar) {
        if(count > 0) {
            bar.style.display = 'flex';
            document.getElementById('selectedCount').innerText = count + ' items selected';
        } else {
            bar.style.display = 'none';
        }
    }
}
</script>
</body>
</html>
