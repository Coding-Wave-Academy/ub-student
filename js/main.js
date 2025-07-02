// Load and display study entries
        function loadStudyEntries() {
            let entries = JSON.parse(localStorage.getItem('personal_study_timetable') || '[]');
            
            // Seed with 10 sample entries if empty
            if (entries.length === 0) {
                entries = [
                    { day: 'Monday', time: '09:00', subject: 'Mathematics II' },
                    { day: 'Monday', time: '11:00', subject: 'Python Programming' },
                    { day: 'Tuesday', time: '08:00', subject: 'Data Structures' },
                    { day: 'Tuesday', time: '14:00', subject: 'Computer Networks' },
                    { day: 'Wednesday', time: '10:00', subject: 'Database Systems' },
                    { day: 'Wednesday', time: '15:00', subject: 'Web Development' },
                    { day: 'Thursday', time: '09:00', subject: 'Software Engineering' },
                    { day: 'Thursday', time: '13:00', subject: 'Operating Systems' },
                    { day: 'Friday', time: '11:00', subject: 'Machine Learning' },
                    { day: 'Friday', time: '16:00', subject: 'Cyber Security' }
                ];
                localStorage.setItem('personal_study_timetable', JSON.stringify(entries));
            }

            const tbody = document.getElementById('studyEntriesBody');
            if (tbody) {
                tbody.innerHTML = entries.map(entry => 
                    `<tr>
                        <td>${entry.day}</td>
                        <td>${entry.time}</td>
                        <td>${entry.subject}</td>
                    </tr>`
                ).join('');
            }
        }
