<!-- View cho form tìm kiếm -->
 <!-- /app/views/job/search.php -->
<form method="GET" action="/index.php?action=results">
    <input type="text" name="keyword" placeholder="Keyword (e.g. Software Engineer)">
    <input type="text" name="company_name" placeholder="Company Name">
    <input type="text" name="job_id" placeholder="Job ID">

    <input type="number" name="minSalary" placeholder="Min Salary">
    <input type="number" name="maxSalary" placeholder="Max Salary">
    <input type="text" name="location" placeholder="Location">


    <select name="sort_by">
        <option value="">-- Sort By --</option>
        <option value="date">Date Posted (Newest)</option>
        <option value="salary">Salary (High to Low)</option>
        <option value="relevance">Relevance</option>
    </select>

    <select name="category">
        <option value="">-- Job Category --</option>
        <option value="IT">IT</option>
        <option value="Marketing">Marketing</option>
    </select>

    <select name="experience">
        <option value="">-- Experience Level --</option>
        <option value="entry">Entry</option>
        <option value="mid">Mid</option>
        <option value="senior">Senior</option>
    </select>

    <select name="job_type">
        <option value="">-- Job Type --</option>
        <option value="full-time">Full-time</option>
        <option value="part-time">Part-time</option>
        <option value="internship">Internship</option>
        <option value="contract">Contract</option>
    </select>

    <select name="remote">
        <option value="">-- Remote/Onsite --</option>
        <option value="remote">Remote</option>
        <option value="onsite">Onsite</option>
    </select>

    <input type="text" name="industry" placeholder="Industry (optional)">

    <button type="submit">Search</button>
</form>

