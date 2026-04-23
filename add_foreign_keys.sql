-- Add missing foreign key constraints
-- This script preserves existing data and adds only missing foreign keys

-- JobOffer foreign keys
ALTER TABLE job_offer 
ADD CONSTRAINT FK_288A3A4E156BE243 FOREIGN KEY (recruiter_id) REFERENCES app_user(id);

ALTER TABLE job_offer 
ADD CONSTRAINT FK_288A3A4E12469DE2 FOREIGN KEY (category_id) REFERENCES job_category(id);

-- JobRequest foreign keys  
ALTER TABLE job_request 
ADD CONSTRAINT FK_A178380491BD8781 FOREIGN KEY (candidate_id) REFERENCES app_user(id);

ALTER TABLE job_request 
ADD CONSTRAINT FK_A17838043481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer(id);

-- Complaint foreign keys
ALTER TABLE complaint 
ADD CONSTRAINT FK_5F2732B5A76ED395 FOREIGN KEY (user_id) REFERENCES app_user(id);

ALTER TABLE complaint 
ADD CONSTRAINT FK_5F2732B5C54C8C93 FOREIGN KEY (type_id) REFERENCES reclaim_type(id);

-- Response foreign key
ALTER TABLE response 
ADD CONSTRAINT FK_3E7B0BFB642B8210 FOREIGN KEY (admin_id) REFERENCES app_user(id);

-- Training foreign key
ALTER TABLE training 
ADD CONSTRAINT FK_D5128A8F642B8210 FOREIGN KEY (admin_id) REFERENCES app_user(id);

-- AppEvent foreign key
ALTER TABLE app_event 
ADD CONSTRAINT FK_ED7D876A876C4DDA FOREIGN KEY (organizer_id) REFERENCES app_user(id);

-- Quiz foreign key
ALTER TABLE quiz 
ADD CONSTRAINT FK_A412FA92A72C360F FOREIGN KEY (related_job_id) REFERENCES job_offer(id);

-- Question foreign key
ALTER TABLE question 
ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz(id);

-- QuizResult foreign keys
ALTER TABLE quiz_result 
ADD CONSTRAINT FK_FE2E314A853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz(id);

ALTER TABLE quiz_result 
ADD CONSTRAINT FK_FE2E314A91BD8781 FOREIGN KEY (candidate_id) REFERENCES app_user(id);

-- Interview foreign key
ALTER TABLE interview 
ADD CONSTRAINT FK_CF1D3C34280928B8 FOREIGN KEY (job_request_id) REFERENCES job_request(id);

-- EventParticipant foreign keys (already exist but let's ensure they're correct)
-- These were already created, so we skip them to avoid conflicts
