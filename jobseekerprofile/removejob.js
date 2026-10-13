document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-btn');
        if (!button) return;

        event.preventDefault();
        event.stopPropagation();

        const jobId = button.getAttribute('data-jobid');
        const jobseekerId = button.getAttribute('data-jobseekerid');
        const item = document.getElementById('saved-job-' + jobId);

        if (!confirm(window.lang && window.lang.remove_confirm ? window.lang.remove_confirm : 'Are you sure you want to remove this job from your saved list?')) {
            return;
        }

        const params = new URLSearchParams();
        params.append('job_id', jobId);
        params.append('jobseeker_id', jobseekerId);

        fetch('removesavedjobs.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body: params.toString()
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                if (response.success) {
                    alert(window.lang && window.lang.remove_success ? window.lang.remove_success : 'Job removed successfully!');
                    if (item) {
                        item.remove();
                    }

                    const remainingItems = document.querySelectorAll('.saved-job-item').length;
                    const container = document.querySelector('.saved-jobs-container');
                    if (container && remainingItems === 0) {
                        container.innerHTML = '<div style="color: #888; text-align: center; padding: 20px;">No saved jobs found.</div>';
                    }
                } else {
                    alert((window.lang && window.lang.remove_error ? window.lang.remove_error : 'Error removing job:') + ' ' + (response.message || 'Unknown error'));
                    console.error('Remove saved job error:', response.error || response.message);
                }
            })
            .catch(function (error) {
                console.error('Remove saved job request failed:', error);
                alert(window.lang && window.lang.remove_failed ? window.lang.remove_failed : 'An error occurred while trying to remove the job.');
            });
    });
});