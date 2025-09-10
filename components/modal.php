<!-- Add Topic-->
<div class="modal fade" id="topic" tabindex="-1" role="dialog" aria-labelledby="formModal" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="formModal">Add Project</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form class="">
          <div class="form-group">
            <label>Project Name</label>
            <div class="input-group">
              <input type="text" name="project_name" class="form-control" required>
            </div>
          </div>
          <div class="form-group">
            <label>Project Case Study</label>
            <div class="input-group">
              <input type="text" name="project_case" class="form-control" required>
            </div>
          </div>
          <div class="form-group">
            <label>Project Level</label>
            <div class="input-group">
              <select name="project_level" class="form-control" required>
                <option>--Select Level--</option> 
                <option>ND</option>
                <option>HND</option>
              </select>
            </div>
          </div>
          <button type="button" class="btn btn-primary m-t-15 waves-effect">Submit</button>
        </form>
      </div>
    </div>
  </div>
</div>